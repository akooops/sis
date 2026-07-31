<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletters', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('name');
            // Nullable: a publish-only issue has no email subject line.
            $table->string('subject')->nullable();
            $table->longText('content')->nullable();

            $table->boolean('is_published')->default(false);
            $table->string('published_status')->default('draft')->index();
            $table->dateTime('published_at')->nullable();

            $table->boolean('is_sendable')->default(true);
            $table->string('sent_status')->default('draft')->index();
            $table->dateTime('sent_at')->nullable();

            $table->json('title')->nullable();

            $table->ulid('integration_id')->nullable();
            $table->foreign('integration_id')->references('id')->on('integrations')->nullOnDelete();

            $table->index(['published_status', 'published_at']);
            $table->index(['sent_status', 'sent_at']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletters');
    }
};
