<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Narrows which records a model-typed setting may point at — "an AI integration"
 * rather than "any integration".
 *
 * Written in the query-contract vocabulary the index endpoints already speak
 * (filter[key] => value). That is the whole point: the picker sends this array to
 * the API verbatim, and App\Rules\SettingReference applies the same one through
 * Setting::referenceQuery(). One declaration, so the list the admin is offered and
 * the list the server accepts cannot drift apart.
 *
 * An alter rather than a column folded into create_settings_table: rows already
 * carry values by the time this lands, and rebuilding the table would take them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->json('filter')->nullable()->after('options');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('filter');
        });
    }
};
