<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What happened at one field during one submission.
 *
 * The only per-field table in the module, and it earns that because the
 * headline analytic — which field loses people — aggregates ACROSS submissions
 * grouped by field. Held as JSON on form_submissions that would be a full scan
 * on every dashboard load; here it is an indexed group-by.
 *
 * Counts only. Keystrokes and pasted content are never stored, just how many
 * there were: that is a privacy line, not an optimisation.
 *
 * form_field_id is nullOnDelete and field_key is snapshotted, so deleting a
 * field leaves its historical events readable instead of erasing the evidence
 * of why it was deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_submission_field_events', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('field_key');

            $table->unsignedSmallInteger('focus_order')->nullable();
            $table->unsignedInteger('focus_ms')->default(0);
            $table->unsignedSmallInteger('revisits')->default(0);
            $table->unsignedInteger('keystrokes')->default(0);
            $table->unsignedInteger('deletions')->default(0);
            $table->unsignedInteger('final_length')->default(0);
            $table->unsignedSmallInteger('paste_count')->default(0);
            $table->unsignedSmallInteger('error_count')->default(0);
            $table->boolean('is_abandoned')->default(false);
            
            $table->ulid('form_field_id')->nullable();
            $table->foreign('form_field_id', 'fsfe_field_fk')->references('id')->on('form_fields')->nullOnDelete();

            $table->ulid('form_submission_id');
            $table->foreign('form_submission_id', 'fsfe_submission_fk')->references('id')->on('form_submissions')->cascadeOnDelete();

            $table->unique(['form_submission_id', 'form_field_id'], 'fsfe_submission_field_unique');
            $table->index(['form_field_id', 'is_abandoned'], 'fsfe_field_abandoned_index');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submission_field_events');
    }
};
