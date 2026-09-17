<?php

namespace App\Services\Legacy;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The read side of the import: a second MySQL connection, pointed at a restored
 * copy of the old app's database.
 *
 * REGISTERED AT RUNTIME, not declared in config/database.php, because the legacy
 * database is a thing that exists for one afternoon on one machine. Putting it
 * in the app's permanent connection list would mean every environment carries a
 * connection to a database that does not exist, and `php artisan tinker` on a
 * fresh clone would list it as though it were part of the system.
 *
 * STRICTLY READ-ONLY. Nothing in App\Services\Legacy writes through this
 * connection, and the importers only ever call table()/has(). The old database
 * is evidence; the import must be re-runnable against exactly the same bytes.
 */
class LegacyDatabase
{
    /** @var array<string, bool> Memoised table existence, one query each. */
    protected array $tables = [];

    /** @var array<string, bool> Memoised "table.column" existence. */
    protected array $columns = [];

    public function __construct(protected string $connection) {}

    /**
     * Register the connection and prove it answers.
     *
     * Connecting HERE rather than lazily on the first query is deliberate: a bad
     * database name should fail on the command's first line with a message about
     * the database, not halfway through module four with a PDO exception that
     * reads like a bug in an importer.
     */
    public static function connect(string $database): self
    {
        $name = config('legacy.connection', 'legacy');

        Config::set("database.connections.{$name}", array_merge(
            config('database.connections.mysql'),
            config('legacy.database'),
            [
                'database' => $database,
                // Nothing reads a prefix off the legacy side, and inheriting this
                // app's would silently look for `sis_articles`.
                'prefix' => '',
            ],
        ));

        DB::purge($name);

        try {
            DB::connection($name)->getPdo();
        } catch (\Throwable $e) {
            throw new RuntimeException(
                "Could not connect to the legacy database [{$database}]: {$e->getMessage()}"
            );
        }

        return new self($name);
    }

    /** A query builder on a legacy table. */
    public function table(string $table): Builder
    {
        return DB::connection($this->connection)->table($table);
    }

    /**
     * Whether the legacy database actually has this table.
     *
     * The old app grew over a year of migrations, so a dump taken from an older
     * install genuinely may not have `brands` or `facilities`. An importer asks
     * first and reports "not present" — which is information — instead of dying
     * on "Base table or view not found", which reads like a broken import.
     */
    public function has(string $table): bool
    {
        return $this->tables[$table] ??= DB::connection($this->connection)
            ->getSchemaBuilder()
            ->hasTable($table);
    }

    /**
     * Whether a legacy table has a column.
     *
     * Needed as much as has(): the old app added `facility_id` to six content
     * tables late in its life, so a dump taken before that has the tables but not
     * the column, and a `whereNotNull('facility_id')` would throw where the right
     * answer is "this install never had facilities".
     */
    public function hasColumn(string $table, string $column): bool
    {
        return $this->columns[$table.'.'.$column] ??= $this->has($table)
            && DB::connection($this->connection)->getSchemaBuilder()->hasColumn($table, $column);
    }

    /** Row count, for the progress line an importer prints before it starts. */
    public function count(string $table): int
    {
        return $this->has($table) ? $this->table($table)->count() : 0;
    }

    public function name(): string
    {
        return $this->connection;
    }
}
