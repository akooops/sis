<?php

namespace App\Services\Jobs;

use App\Ai\Prompts\ParseCv;
use App\Ai\Runner;
use App\Models\Country;
use App\Models\Media;
use App\Services\Integrations\Ai;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Turn an uploaded CV into answers the form can be prefilled with.
 *
 * INTERACTIVE, NOT QUEUED — the applicant is waiting, and the whole point is that
 * the form comes back already filled in. Everything else in app/Jobs/Ai runs
 * behind the response; this one cannot.
 *
 * THE FILE IS SENT AS A FILE. This used to pull text out of the PDF here — regex
 * over `stream…endstream`, gzuncompress, `(…) Tj` — which returned nothing usable
 * for most real CVs (compressed object streams, CID fonts, two columns) and, worse,
 * could not tell an empty extraction from an empty CV. The provider does its own
 * extraction now; nothing in this app parses a document format.
 *
 * EVERY FAILURE IS AN EMPTY ARRAY, logged. Unreadable file type, missing file,
 * no AI integration, an integration whose driver cannot read documents, a
 * provider that refuses or times out — all of it lands in the same `return []`,
 * because the fallback is the blank form the applicant would have filled in
 * anyway. SubmitController::parseCv() catches on top of this as a backstop, but
 * the promise belongs here: nothing that goes wrong reading a CV may be visible
 * to the person applying.
 *
 * WHAT COMES BACK IS A SUGGESTION. It is written into the renderer's initial
 * values and the applicant reviews every field before submitting. Nothing here
 * bypasses validation, the guards or the projector — a parsed application takes
 * exactly the same path as a typed one.
 */
class CvParser
{
    public function __construct(protected Runner $runner) {}

    /**
     * @return array<string, mixed> answers keyed by the form's own field keys
     */
    public function parse(Media $media): array
    {
        try {
            $document = $this->document($media);

            if ($document === null) {
                return [];
            }

            return $this->shape($this->runner->run(new ParseCv, $document));
        } catch (Throwable $e) {
            Log::channel('integrations')->warning('jobs.cv-parse-failed', [
                'media' => $media->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * The file, ready to hand over — or null when it cannot be read at all.
     *
     * The type is checked against what the PROVIDER accepts rather than against
     * a list kept here: the field takes pdf, doc and docx, OpenAI reads PDF, and
     * a Word CV has to fall through to the blank form instead of being posted to
     * an API that will refuse it. The upload itself is untouched either way —
     * the file is stored, attached and reaches the reviewer as normal.
     *
     * Reads `disk` off the row rather than assuming: a CV is on quarantine until
     * ScanUpload promotes it, and the path is the same on both.
     *
     * @return array{filename: string, contents: string}|null
     */
    protected function document(Media $media): ?array
    {
        $extension = strtolower(pathinfo((string) $media->file_name, PATHINFO_EXTENSION));

        if (! in_array($extension, Ai::default()->readableDocumentTypes(), true)) {
            Log::channel('integrations')->info('jobs.cv-not-readable', [
                'media' => $media->id,
                'extension' => $extension,
            ]);

            return null;
        }

        $disk = Storage::disk($media->disk);

        if (! $disk->exists($media->path)) {
            return null;
        }

        $contents = (string) $disk->get($media->path);

        // The stored name, not media.name: it is a ULID plus the real extension,
        // so the provider gets the type marker it reads the data URI with and
        // nothing else. The applicant's own filename adds nothing the file does
        // not already say.
        return $contents === '' ? null : ['filename' => $media->file_name, 'contents' => $contents];
    }

    /**
     * Normalise the model's answer into the form's shape.
     *
     * The three repeats arrive as JSON STRINGS — see ParseCv — so they are decoded
     * here. A string that will not parse is dropped rather than guessed at: an
     * empty repeat the applicant fills in themselves beats a malformed one that
     * breaks the renderer.
     *
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    /**
     * The ISO code for whatever the CV called someone's nationality.
     *
     * THE FORM FIELD IS A SELECT WHOSE OPTION VALUES ARE ISO CODES, so a raw
     * answer cannot be handed to the renderer. A CV says "Saudi", the prompt now
     * asks for `SA` — but a model that answers the question as written rather
     * than as instructed produces a demonym or a country name, and seeding that
     * is the worst of the three outcomes: SelectControl matches on `value`, so
     * nothing is selected, the applicant sees an untouched required dropdown, and
     * the FIRST SUBMIT FAILS with "the selected nationality is invalid" against a
     * field that looks empty. Only on the AI path, so nobody would reproduce it.
     *
     * Resolved against the same three columns the option list is built from —
     * the code, the translated demonym, the translated country name — in every
     * locale, because a CV in Arabic says "سعودي". An answer that resolves to
     * nothing is DROPPED rather than passed through: a blank the applicant fills
     * in is a five-second job, and a rejected submit is not.
     */
    protected function country(mixed $answer): ?string
    {
        $needle = trim(mb_strtolower((string) $answer));

        if ($needle === '') {
            return null;
        }

        if (strlen($needle) === 2 && ($code = Country::where('code', strtoupper($needle))->value('code'))) {
            return $code;
        }

        // One pass over 249 rows in PHP rather than nine LIKEs over two JSON
        // columns. This runs once, interactively, on a table that is a fixture.
        foreach (Country::query()->get(['code', 'name', 'title', 'nationality']) as $country) {
            $names = array_merge(
                [$country->name],
                array_values($country->getTranslations('nationality')),
                array_values($country->getTranslations('title')),
            );

            foreach ($names as $name) {
                if (trim(mb_strtolower((string) $name)) === $needle) {
                    return $country->code;
                }
            }
        }

        Log::channel('integrations')->info('jobs.cv-nationality-unresolved', ['answer' => $answer]);

        return null;
    }

    protected function shape(array $result): array
    {
        $out = [];

        foreach (['first_name', 'last_name', 'email', 'phone', 'address'] as $key) {
            if (! empty($result[$key])) {
                $out[$key] = $result[$key];
            }
        }

        // Nationality is the one answer that cannot be passed through: the field
        // is a select over ISO codes. See country().
        if ($code = $this->country($result['nationality'] ?? null)) {
            $out['nationality'] = $code;
        }

        if (! empty($result['skills'])) {
            $out['skills'] = $result['skills'];
        }

        foreach (['education', 'experience', 'languages'] as $key) {
            $decoded = json_decode((string) ($result[$key] ?? ''), true);

            if (is_array($decoded) && $decoded !== []) {
                $out[$key] = array_values(array_filter($decoded, 'is_array'));
            }
        }

        return $out;
    }
}
