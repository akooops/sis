<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('newsletters', function (Blueprint $table) {
            // The website pipeline, deliberately separate from `status`, which is the
            // email one: is_published says the issue belongs on the site, this says when.
            $table->string('publish_status')->default('draft')->index()->after('is_sendable');
            $table->dateTime('published_at')->nullable()->after('publish_status');

            // What newsletters:publish-scheduled reads on every tick.
            $table->index(['publish_status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::table('newsletters', function (Blueprint $table) {
            $table->dropIndex(['publish_status', 'published_at']);
            $table->dropIndex(['publish_status']);
            $table->dropColumn(['publish_status', 'published_at']);
        });
    }
};
