<?php

namespace Database\Seeders;

use App\Models\JobOffer;
use App\States\JobOffer\Published;
use Illuminate\Database\Seeder;

/**
 * The one posting that ships with the app: an always-open general application, so
 * a spontaneous CV has somewhere to land when nothing suitable is advertised.
 *
 * It is `is_system`, which freezes its slug and blocks the delete — the apply flow
 * resolves it by slug, and a rename would strand every spontaneous applicant.
 *
 * A SEEDED ROW RATHER THAN A NULLABLE job_offer_id, because dedup lives on
 * `unique(job_offer_id, candidate_id)` and MySQL treats every NULL in a unique
 * index as distinct — a nullable column would switch dedup off for precisely the
 * applications most likely to be duplicated.
 *
 * firstOrCreate, so reseeding an install never overwrites wording an admin has
 * since edited. Only the lock is re-asserted.
 */
class JobOffersSeeder extends Seeder
{
    public function run(): void
    {
        $titles = [
            'en' => 'General application',
            'ar' => 'طلب توظيف عام',
            'fr' => 'Candidature spontanée',
            'es' => 'Candidatura espontánea',
            'de' => 'Initiativbewerbung',
            'it' => 'Candidatura spontanea',
            'pt' => 'Candidatura espontânea',
            'ru' => 'Общая заявка',
            'hi' => 'सामान्य आवेदन',
        ];

        $descriptions = [
            'en' => 'Send us your CV and we will keep it on file for suitable openings.',
            'ar' => 'أرسل لنا سيرتك الذاتية وسنحتفظ بها للوظائف المناسبة.',
            'fr' => 'Envoyez-nous votre CV : nous le conserverons pour les postes adaptés.',
            'es' => 'Envíenos su CV y lo conservaremos para las vacantes adecuadas.',
            'de' => 'Senden Sie uns Ihren Lebenslauf; wir behalten ihn für passende Stellen.',
            'it' => 'Inviaci il tuo CV: lo conserveremo per le posizioni adatte.',
            'pt' => 'Envie-nos o seu CV e guardá-lo-emos para vagas adequadas.',
            'ru' => 'Отправьте нам своё резюме — мы сохраним его для подходящих вакансий.',
            'hi' => 'हमें अपना सीवी भेजें; हम इसे उपयुक्त रिक्तियों के लिए सुरक्षित रखेंगे।',
        ];

        $offer = JobOffer::firstOrCreate(
            ['slug' => JobOffer::GENERAL_SLUG],
            [
                'name' => 'General application',
                'employment_type' => 'other',
                'work_mode' => 'onsite',
                'status' => Published::class,
                'published_at' => now(),
                // No deadline, ever: this is the posting that is always open.
                'deadline_at' => null,
                'title' => $titles,
                'description' => $descriptions,
            ],
        );

        /*
         * BOTH HALVES OF THE CONTRACT RE-ASSERTED ON EVERY SEED, not only on
         * create. `is_system` because an install predating the column would
         * otherwise keep a deletable general posting — and `deadline_at` because
         * this row is the one that is ALWAYS open, and firstOrCreate cannot repair
         * a deadline somebody has since set on it.
         *
         * Not hypothetical: this row was found with is_system cleared AND a
         * deadline two weeks out, which would have closed the posting the apply
         * flow resolves by slug and left every spontaneous applicant with the
         * "closed" notice instead of a form.
         */
        if ($offer->is_system !== true || $offer->deadline_at !== null) {
            $offer->forceFill(['is_system' => true, 'deadline_at' => null])->saveQuietly();
        }
    }
}
