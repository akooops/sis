<?php

namespace App\Services\Legacy;

use Illuminate\Console\OutputStyle;

/**
 * Everything an importer needs, and the tally it leaves behind.
 *
 * One object passed down rather than five constructor arguments repeated across
 * twenty importers — and, more usefully, ONE PLACE THE RUN'S REPORT IS BUILT.
 * A migration you cannot audit afterwards is a migration you have to trust; the
 * counters and notes gathered here are what the command prints at the end, so
 * "846 articles, 3 with no thumbnail on disk" is a fact rather than a hope.
 */
class LegacyContext
{
    /** @var array<string, array{created: int, updated: int, skipped: int}> */
    protected array $counts = [];

    /** @var array<int, array{module: string, message: string}> */
    protected array $notes = [];

    /** @var array<int, array{module: string, message: string}> */
    protected array $warnings = [];

    protected string $module = '';

    public function __construct(
        public readonly LegacyDatabase $db,
        public readonly LegacyIdMap $map,
        public readonly LegacyFiles $files,
        public readonly LegacyTranslations $translations,
        public readonly OutputStyle $output,
        /** Read everything, write nothing. */
        public readonly bool $dryRun = false,
        /**
         * The region to read a LOCAL phone number as, e.g. `SA`.
         *
         * App\Services\Phone\PhoneFormatter deliberately refuses to guess one —
         * a default region turns a typo into a valid number somewhere else — and
         * that rule is right for a form somebody is filling in now. Legacy data
         * is a different problem: the old app stored whatever was typed, so a
         * table full of `05xxxxxxxx` cannot be normalised at all without being
         * told where it came from, and un-normalised numbers are what make
         * Candidate and Visitor dedup miss.
         *
         * So it is an explicit operator decision (`--phone-region`) about one
         * install's history, never a default.
         */
        public readonly ?string $phoneRegion = null,
    ) {}

    /** Numbers no rule could turn into E164, counted for the report. */
    public int $rawPhones = 0;

    /** Legacy slots folded onto an existing one — see LegacyImporter::slot(). */
    public int $collapsedSlots = 0;

    /** Identifying values refused to avoid a unique clash — see identify(). */
    public int $identityClashes = 0;

    /** Scope the counters and notes that follow to one module. */
    public function enter(string $module): void
    {
        $this->module = $module;
        $this->counts[$module] ??= ['created' => 0, 'updated' => 0, 'skipped' => 0];
    }

    public function created(int $n = 1): void
    {
        $this->counts[$this->module]['created'] += $n;
    }

    public function updated(int $n = 1): void
    {
        $this->counts[$this->module]['updated'] += $n;
    }

    public function skipped(int $n = 1): void
    {
        $this->counts[$this->module]['skipped'] += $n;
    }

    /**
     * Something the operator should know but that is not a problem — a legacy
     * column this app has no home for, a settings key with no destination.
     */
    public function note(string $message): void
    {
        $this->notes[] = ['module' => $this->module, 'message' => $message];
    }

    /** Something that lost data or could not be resolved. */
    public function warn(string $message): void
    {
        $this->warnings[] = ['module' => $this->module, 'message' => $message];
    }

    /** @return array<string, array{created: int, updated: int, skipped: int}> */
    public function counts(): array
    {
        return $this->counts;
    }

    /** @return array<int, array{module: string, message: string}> */
    public function notes(): array
    {
        return $this->notes;
    }

    /** @return array<int, array{module: string, message: string}> */
    public function warnings(): array
    {
        return $this->warnings;
    }

    public function hasWarnings(): bool
    {
        return $this->warnings !== [];
    }
}
