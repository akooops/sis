<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The legacy import's memory: legacy integer id => the ULID row it became.
 *
 * THIS TABLE IS WHAT MAKES `legacy:import` RE-RUNNABLE. The old app keys on
 * auto-increment integers and this one on ULIDs, so there is no arithmetic that
 * turns one into the other — and most of what has to be carried across has no
 * natural key to match on instead (a grade is a name inside a programme, a menu
 * item is a name inside a menu, a time slot is two datetimes). Without a
 * recorded mapping, a second run would insert everything a second time and
 * every cross-reference resolved in the first run would point at the orphan.
 *
 * `source` is the LEGACY table name, not the new one: several legacy tables land
 * in the same new table (newsletters and files both become media-bearing rows,
 * achievement_categories becomes a category) and the pair that must be unique is
 * the one on the legacy side.
 *
 * It is a record of a migration, not application state — nothing outside
 * App\Services\Legacy reads it, and it can be dropped once the import is done
 * and verified.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_imports', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('source');
            $table->unsignedBigInteger('legacy_id');

            $table->string('model_type');
            $table->ulid('model_id');

            $table->timestamps();

            // One legacy row maps to exactly one new row. This index is the
            // idempotency guarantee, not merely an optimisation.
            $table->unique(['source', 'legacy_id']);

            // Reverse lookups: "what did this row come from?", and the sweep that
            // drops mappings whose target has since been deleted by hand.
            $table->index(['model_type', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_imports');
    }
};
