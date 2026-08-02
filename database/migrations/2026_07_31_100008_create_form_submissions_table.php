<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per submission — the answers AND everything measured about them.
 *
 * THE ULID `id` IS THE REFERENCE. There is no second human-quotable code: the id
 * is what the thank-you page shows the visitor, what the webhook payload
 * carries, what the CSV's first column holds and what the admin searches on. A
 * separate `reference` column meant a second unique index, an allocation retry
 * on every insert and two identifiers for one row.
 *
 * ANSWERS live in `data`, keyed by the field's machine key, IN THE FORM'S
 * READING ORDER — page order, then field order within the page — with an
 * explicit null for every capturing field the visitor left blank. So the map is
 * a complete, ordered picture of the form rather than only the parts that came
 * back, and a blank field is visibly unanswered instead of merely absent.
 * `fields` holds a snapshot of {key: {label, type}} taken at submit time, in
 * that same order, and is what keeps a two-year-old submission readable after
 * its form was renamed, relabelled or had fields removed: nothing here re-reads
 * the live form.
 *
 * NO `user_id`. These forms are public and anonymous; the visitor is described
 * by what was measured about them, not by an account they do not have.
 *
 * The trade to know: a unique-within-form field is a JSON path lookup
 * (`where('data->email', …)`) rather than an indexed column. That is fine at the
 * volume a form runs at, and if one ever gets large the fix is a single MySQL
 * generated column plus an index on that one path — no schema rework.
 *
 * `status` is a plain string, not a spatie state machine: it is written once by
 * the server per outcome, no admin transitions it, a TransitionNotFound on the
 * public path would 500 the form, and the abandonment sweep is a bulk builder
 * update that a state machine forbids.
 *
 * `spam_score` is clamped to 100 before it is written — the additive reason
 * table sums well past 255 and a tinyint overflow throws under strict mode,
 * which would 500 the public submit path on exactly the worst-case spam hit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('status', 32)->default('started');
            $table->string('session_id', 64)->nullable();

            /* Answers */
            $table->json('data')->nullable();
            $table->json('fields')->nullable();

            /* Spam */
            $table->unsignedTinyInteger('spam_score')->default(0);
            $table->json('spam_reasons')->nullable();
            $table->boolean('is_honeypot_triggered')->default(false);
            $table->json('validation_errors')->nullable();

            /* Timing. Timestamps are UTC; the offset is kept beside them, which
               is what "timestamp with timezone" means in practice. */
            $table->dateTime('loaded_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->string('timezone', 64)->nullable();
            $table->smallInteger('timezone_offset_minutes')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedInteger('ttfi_ms')->nullable();
            $table->unsignedInteger('fill_ms')->nullable();
            $table->unsignedInteger('time_on_page_ms')->nullable();

            /* Identity and geography. ip_hash is always written (it enforces the
               caps and the IP block); ip_address is the readable copy and can be
               switched off per form. */
            $table->char('ip_hash', 64)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->char('fingerprint', 64)->nullable();

            $table->char('country_code', 2)->nullable();
            $table->string('city')->nullable();

            /* Device */
            $table->text('user_agent')->nullable();
            $table->string('browser', 64)->nullable();
            $table->string('os', 64)->nullable();
            $table->string('device_type', 16)->nullable();
            $table->unsignedSmallInteger('screen_w')->nullable();
            $table->unsignedSmallInteger('screen_h')->nullable();
            $table->unsignedSmallInteger('viewport_w')->nullable();
            $table->unsignedSmallInteger('viewport_h')->nullable();
            $table->string('browser_language', 32)->nullable();
            $table->string('connection', 32)->nullable();

            /* Acquisition */
            $table->string('page_url', 2048)->nullable();
            $table->string('landing_url', 2048)->nullable();
            $table->string('referrer', 2048)->nullable();
            $table->json('utm')->nullable();
            $table->string('entry_point', 32)->nullable();

            /* Behaviour and funnel */
            $table->json('focus_order')->nullable();
            $table->unsignedInteger('validation_error_count')->default(0);
            $table->unsignedInteger('submit_attempts')->default(0);
            $table->unsignedInteger('back_navigations')->default(0);
            $table->unsignedTinyInteger('scroll_depth_percent')->nullable();
            $table->unsignedInteger('click_count')->default(0);
            $table->unsignedInteger('paste_count')->default(0);
            $table->unsignedSmallInteger('pages_completed')->default(0);
            $table->json('steps')->nullable();

            $table->ulid('form_id');
            $table->foreign('form_id')->references('id')->on('forms')->cascadeOnDelete();

            $table->ulid('last_form_page_id')->nullable();
            $table->foreign('last_form_page_id')->references('id')->on('form_pages')->nullOnDelete();

            $table->ulid('abandoned_form_field_id')->nullable();
            $table->foreign('abandoned_form_field_id')->references('id')->on('form_fields')->nullOnDelete();
            $table->string('abandoned_field_key')->nullable();

            $table->ulid('country_id')->nullable();
            $table->foreign('country_id')->references('id')->on('countries')->nullOnDelete();

            $table->index(['form_id', 'status']);
            $table->index(['form_id', 'submitted_at']);
            $table->index(['form_id', 'ip_hash']);
            $table->index(['form_id', 'fingerprint']);
            $table->index('device_type');
            $table->index(['ip_hash', 'created_at']);
            $table->unique(['form_id', 'session_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
    }
};
