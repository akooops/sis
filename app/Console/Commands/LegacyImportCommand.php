<?php

namespace App\Console\Commands;

use App\Services\Legacy\Importers;
use App\Services\Legacy\LegacyContext;
use App\Services\Legacy\LegacyDatabase;
use App\Services\Legacy\LegacyFiles;
use App\Services\Legacy\LegacyIdMap;
use App\Services\Legacy\LegacyImporter;
use App\Services\Legacy\LegacyTranslations;
use App\Services\Notifications\NotificationService;
use Illuminate\Console\Command;
use RuntimeException;
use Throwable;

/**
 * ONE COMMAND THAT MOVES THE OLD APP INTO THIS ONE.
 *
 *   php artisan legacy:import --legacy=sis_old --files=/path/to/storage/app/public
 *
 * That is the whole deployment step: restore the old dump into a database, put
 * the old `storage/app/public` somewhere readable, run this. It is SAFE TO RUN
 * AGAIN — every importer reconciles through `legacy_imports` rather than
 * inserting blind — so the ordinary way to use it is to run it, read the report,
 * fix a mapping in config/legacy.php, and run it again.
 *
 * ── WHAT IS SWITCHED OFF WHILE IT RUNS, AND WHY ──────────────────────────────
 *
 * Three of this app's standing behaviours are wrong for a migration, and all
 * three would be discovered the expensive way:
 *
 *  - THE AUDIT LOG. Observers write an activity row per model write. Importing
 *    fifty thousand rows would bury every real audit row under a machine's work —
 *    the same reasoning that already keeps Session, CandidateMatch and
 *    SitePageView unobserved.
 *  - NOTIFICATIONS. JobApplicationObserver announces a new application; importing
 *    three thousand of them would send three thousand notices about things that
 *    happened last year.
 *  - QUEUED JOBS. JobOfferObserver dispatches an embedding on write, and the
 *    projectors dispatch scoring. On a sync queue that is a paid API call per
 *    row. The queue is pointed at the `null` driver, which discards everything
 *    dispatched — so ScanUpload is skipped too, which is correct: these files
 *    were served publicly for years and are imported already `clean`.
 *
 * `--with-ai` keeps the queue as configured, for an operator who does want the
 * embeddings built as part of the run. The better order is usually to import
 * first and re-embed afterwards, once the data is known to be right.
 */
class LegacyImportCommand extends Command
{
    protected $signature = 'legacy:import
        {--legacy= : The legacy MySQL database name (or set LEGACY_DB_DATABASE)}
        {--files= : Path to the old storage/app/public (or its uploads folder)}
        {--only=* : Run only these modules}
        {--except=* : Skip these modules}
        {--strip-host= : An absolute site URL to strip out of imported HTML, e.g. https://old.example.com}
        {--phone-region= : Read local legacy phone numbers as this region, e.g. SA}
        {--allow-missing-files : Create media rows for files that are not on disk}
        {--with-ai : Leave the queue connected, so embeddings and scoring are dispatched}
        {--dry-run : Read everything, write nothing}
        {--list : Print the modules and exit}';

    protected $description = 'Import the old SIS database and its uploaded files into this app';

    /**
     * THE RUN ORDER, AND IT IS THE DESIGN.
     *
     * Read top to bottom: nothing may depend on something below it. Two
     * placements are load-bearing rather than arbitrary:
     *
     *  - `files` FIRST, because every content importer attaches a file rather
     *    than importing one, and free media is how editor images survive.
     *  - `menus` EARLY but `menu-items` LAST. A page carries a `menu_id`, and a
     *    menu item can link to anything — so the two halves of the menu module sit
     *    at opposite ends of the run, which is the only arrangement with no cycle.
     *
     * @var array<int, class-string<LegacyImporter>>
     */
    protected const PIPELINE = [
        Importers\LanguagesImporter::class,
        Importers\FilesImporter::class,
        Importers\MenusImporter::class,
        Importers\CategoriesImporter::class,
        Importers\PagesImporter::class,
        Importers\ArticlesImporter::class,
        Importers\AlbumsImporter::class,
        Importers\EventsImporter::class,
        Importers\AchievementsImporter::class,
        Importers\ProgramsImporter::class,
        Importers\BannersImporter::class,
        Importers\PartnersImporter::class,
        Importers\BrandsImporter::class,
        Importers\CalendarsImporter::class,
        Importers\DocumentsImporter::class,
        Importers\JobOffersImporter::class,
        Importers\VisitServicesImporter::class,
        Importers\FacilitiesImporter::class,
        Importers\SettingsImporter::class,
        Importers\SubmissionsImporter::class,
        Importers\ApplicationsImporter::class,
        Importers\ReservationsImporter::class,
        Importers\MenuItemsImporter::class,
    ];

    public function handle(): int
    {
        if ($this->option('list')) {
            return $this->listModules();
        }

        try {
            $context = $this->boot();
        } catch (RuntimeException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $importers = $this->selected($context);

        if ($importers === []) {
            $this->components->error('No modules selected. Run with --list to see the names.');

            return self::FAILURE;
        }

        $this->preflight($context, $importers);

        $failed = $this->quietly(fn () => $this->runPipeline($context, $importers));

        $this->report($context);

        if ($failed !== []) {
            $this->components->error('Failed: '.implode(', ', $failed).'. Fix and run again — completed modules are skipped.');

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->components->warn(
                'Dry run: nothing was written. Warnings about rows "not imported" are expected here — '
                .'a dry run writes no id mappings, so nothing downstream can resolve what it depends on.'
            );
        }

        return self::SUCCESS;
    }

    /* -----------------------------------------
     Setup
    ------------------------------------------*/

    /** Connect, resolve the files, and assemble what every importer shares. */
    protected function boot(): LegacyContext
    {
        $database = $this->option('legacy') ?: config('legacy.database.database');

        if (! $database) {
            throw new RuntimeException('Give the legacy database with --legacy=<name>, or set LEGACY_DB_DATABASE.');
        }

        $db = LegacyDatabase::connect($database);

        $root = LegacyFiles::resolveRoot($this->option('files') ?: config('legacy.files'));

        $map = new LegacyIdMap;

        $stale = $map->prune();

        if ($stale > 0) {
            $this->components->info("Dropped {$stale} stale mapping(s) whose imported row has since been deleted.");
        }

        $this->components->info("Reading from `{$database}`".($root ? ", files from `{$root}`" : ', no files directory').'.');

        return new LegacyContext(
            db: $db,
            map: $map,
            files: new LegacyFiles($db, $map, $root, (bool) $this->option('allow-missing-files')),
            translations: new LegacyTranslations($db, $this->stripHost()),
            output: $this->output,
            dryRun: (bool) $this->option('dry-run'),
            phoneRegion: $this->option('phone-region') ? strtoupper(trim($this->option('phone-region'))) : null,
        );
    }

    /** The host to strip out of imported HTML, without a trailing slash. */
    protected function stripHost(): ?string
    {
        $host = $this->option('strip-host');

        return $host ? rtrim(trim($host), '/') : null;
    }

    /**
     * The importers this run will execute, in pipeline order.
     *
     * `--only` does NOT pull dependencies in automatically. Importing `articles`
     * alone against a database whose `files` have already been imported is a
     * legitimate and common thing to want, and silently re-running four other
     * modules because of a flag would be surprising; what it does instead is warn
     * when a dependency has never run at all.
     *
     * @return array<int, LegacyImporter>
     */
    protected function selected(LegacyContext $context): array
    {
        $only = array_filter((array) $this->option('only'));
        $except = array_filter((array) $this->option('except'));

        $importers = [];

        foreach (self::PIPELINE as $class) {
            /** @var LegacyImporter $importer */
            $importer = new $class($context);
            $module = $importer->module();

            if ($only && ! in_array($module, $only, true)) {
                continue;
            }

            if (in_array($module, $except, true)) {
                continue;
            }

            $importers[] = $importer;
        }

        $unknown = array_diff(
            array_merge($only, $except),
            array_map(fn ($class) => (new $class)->module(), self::PIPELINE),
        );

        foreach ($unknown as $name) {
            $this->components->warn("Unknown module [{$name}] — run with --list to see the names.");
        }

        return $importers;
    }

    /** Say what is about to happen, and what the legacy database cannot answer. */
    protected function preflight(LegacyContext $context, array $importers): void
    {
        $rows = [];

        foreach ($importers as $importer) {
            $available = $importer->available();

            $rows[] = [
                $importer->module(),
                $available ? '<fg=green>ready</>' : '<fg=yellow>not in dump</>',
                $importer->describe(),
            ];
        }

        $this->table(['Module', 'Status', 'What it brings across'], $rows);
    }

    /* -----------------------------------------
     Execution
    ------------------------------------------*/

    /**
     * Run the pipeline, and keep going when one module fails.
     *
     * A THROW IS NOT FATAL TO THE RUN. Twenty-three modules and one bad row in
     * the fourth should not cost the other nineteen — everything already written
     * is mapped, so the retry after a fix is cheap. Which modules failed is
     * reported at the end and sets the exit code.
     *
     * @return array<int, string> the modules that threw
     */
    protected function runPipeline(LegacyContext $context, array $importers): array
    {
        $failed = [];

        foreach ($importers as $importer) {
            $module = $importer->module();

            if (! $importer->available()) {
                continue;
            }

            $context->enter($module);

            $this->components->task("importing {$module}", function () use ($importer, $module, &$failed) {
                try {
                    $importer->run();

                    return true;
                } catch (Throwable $e) {
                    $failed[] = $module;

                    $this->newLine();
                    $this->components->error("{$module}: {$e->getMessage()}");
                    $this->line('  <fg=gray>'.$e->getFile().':'.$e->getLine().'</>');

                    return false;
                }
            });
        }

        return $failed;
    }

    /**
     * Run the pipeline with this app's write-time side effects switched off.
     *
     * See the class docblock for why each one is wrong during a migration. The
     * queue swap is a config change rather than Bus::fake(), because fake()
     * RECORDS every dispatch in memory — fifty thousand of them — where `null`
     * discards them.
     */
    protected function quietly(callable $callback): array
    {
        $queue = config('queue.default');

        if (! $this->option('with-ai')) {
            config(['queue.default' => 'null']);
        }

        try {
            return activity()->withoutLogs(
                fn () => NotificationService::withoutNotifications($callback)
            );
        } finally {
            config(['queue.default' => $queue]);
        }
    }

    /* -----------------------------------------
     Reporting
    ------------------------------------------*/

    /**
     * The tally, then everything the run could not carry.
     *
     * The notes are the part worth reading. A migration that only prints row
     * counts asks you to take on faith that nothing was lost; these name each
     * thing that was, and why.
     */
    protected function report(LegacyContext $context): void
    {
        $this->newLine();

        $rows = [];
        $totals = [0, 0, 0];

        foreach ($context->counts() as $module => $count) {
            $rows[] = [$module, $count['created'], $count['updated'], $count['skipped']];
            $totals[0] += $count['created'];
            $totals[1] += $count['updated'];
            $totals[2] += $count['skipped'];
        }

        $rows[] = ['<options=bold>total</>', "<options=bold>{$totals[0]}</>", "<options=bold>{$totals[1]}</>", "<options=bold>{$totals[2]}</>"];

        $this->table(['Module', 'Created', 'Updated', 'Unchanged'], $rows);

        if ($context->rawPhones > 0) {
            $hint = $this->option('phone-region')
                ? ''
                : ' Re-run with --phone-region=SA (or whichever country this install is in) to normalise local numbers.';

            $this->line(
                "  <fg=cyan>note</> <fg=gray>[phones]</> {$context->rawPhones} phone number(s) could not be read as E164 and were kept verbatim. "
                ."Candidate and visitor dedup match on email OR phone, so those people may not be recognised as returning.{$hint}"
            );
        }

        if ($context->identityClashes > 0) {
            $this->line(
                "  <fg=yellow>warn</> <fg=gray>[identity]</> {$context->identityClashes} email/phone value(s) were refused because another record already held them. "
                .'Candidates and visitors are matched on email OR phone while both columns are unique, so a person reached by one '
                .'keeps the other as first written — which also means two records sharing a number were merged into one.'
            );
        }

        if ($context->collapsedSlots > 0) {
            $this->line(
                "  <fg=cyan>note</> <fg=gray>[slots]</> {$context->collapsedSlots} legacy slot(s) named a time window another slot already held and were folded onto it. "
                .'The old tables had no unique index on (owner, starts_at, ends_at) and these do, so a bulk generator run twice left duplicates; '
                .'bookings on either legacy row resolve to the one slot, which took the higher capacity.'
            );
        }

        foreach ($context->notes() as $note) {
            $this->line("  <fg=cyan>note</> <fg=gray>[{$note['module']}]</> {$note['message']}");
        }

        foreach ($context->warnings() as $warning) {
            $this->line("  <fg=yellow>warn</> <fg=gray>[{$warning['module']}]</> {$warning['message']}");
        }

        if ($context->notes() || $context->warnings() || $context->rawPhones || $context->collapsedSlots || $context->identityClashes) {
            $this->newLine();
        }
    }

    /** `--list`: the module names, for --only / --except. */
    protected function listModules(): int
    {
        $rows = [];

        foreach (self::PIPELINE as $class) {
            // Metadata only — see LegacyImporter's constructor for why this is
            // allowed to have no context.
            $importer = new $class;

            $rows[] = [$importer->module(), $importer->describe(), implode(', ', $importer->dependsOn()) ?: '—'];
        }

        $this->table(['Module', 'What it brings across', 'After'], $rows);

        return self::SUCCESS;
    }
}
