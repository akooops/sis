<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marks the seeded general-application posting, the one that is always open so
 * spontaneous CVs have somewhere to land.
 *
 * Same contract `is_system` carries on Page and Form: the row cannot be deleted
 * and its slug cannot change, because the application flow resolves it by slug
 * and a rename would strand every spontaneous applicant. Everything else about
 * it — title, description, wording — stays editable.
 *
 * A seeded row rather than a nullable job_offer_id on job_applications, for the
 * reason given in that table's migration: NULLs do not collide in a unique index,
 * so nullable would disable dedup precisely where it is needed most.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_offers', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('job_offers', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });
    }
};
