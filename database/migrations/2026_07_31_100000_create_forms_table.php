<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A public form. Editorially this is `pages` — slug, publish workflow, own
 * stylesheet — plus the settings that make it collectable: limits, spam
 * defences, and the integrations it pins.
 *
 * `min_submit_seconds` is nullable on purpose: null means "use the configured
 * default", so raising config('forms.spam.min_seconds') moves every form that
 * never overrode it. A non-null value is a deliberate per-form override.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('name');
            $table->string('slug')->unique();

            $table->string('status')->default('draft')->index();
            $table->dateTime('published_at')->nullable();

            $table->string('css_url')->nullable();
            $table->text('custom_css')->nullable();

            $table->boolean('is_system')->default(false);

            $table->boolean('is_limited')->default(false);
            $table->unsignedInteger('submissions_limit')->nullable();
            $table->unsignedInteger('submissions_count')->default(0);

            $table->boolean('is_user_limited')->default(false);
            $table->unsignedInteger('per_user_limit')->nullable();
            $table->string('per_user_limit_by', 16)->default('ip');

            $table->boolean('is_spam_filtered')->default(true);
            $table->unsignedSmallInteger('min_submit_seconds')->nullable();

            $table->boolean('is_captcha_enabled')->default(false);
            $table->boolean('is_ip_stored')->default(true);

            $table->string('confirmation_type', 16)->default('message');
            $table->string('redirect_url')->nullable();

            $table->json('title')->nullable();
            $table->json('description')->nullable();
            $table->json('content')->nullable();
            $table->json('confirmation_message')->nullable();

            $table->ulid('category_id')->nullable();
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();

            $table->ulid('captcha_integration_id')->nullable();
            $table->foreign('captcha_integration_id')->references('id')->on('integrations')->nullOnDelete();


            $table->index(['status', 'published_at']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forms');
    }
};
