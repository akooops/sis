<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('newsletters', function (Blueprint $table) {
            // Public website title, per locale (spatie/laravel-translatable).
            $table->json('title')->nullable()->after('name');

            // Two independent switches, not a type: an issue can be archived on the
            // site, emailed, or both. Existing rows are email-only, hence the defaults.
            $table->boolean('is_published')->default(false)->index()->after('subject');
            $table->boolean('is_sendable')->default(true)->index()->after('is_published');
        });

        // A publish-only issue has no subject line. Raw SQL because ->change()
        // needs doctrine/dbal, which this app does not carry.
        DB::statement('ALTER TABLE newsletters MODIFY subject VARCHAR(255) NULL');
    }

    public function down(): void
    {
        Schema::table('newsletters', function (Blueprint $table) {
            $table->dropIndex(['is_published']);
            $table->dropIndex(['is_sendable']);
            $table->dropColumn(['title', 'is_published', 'is_sendable']);
        });
    }
};
