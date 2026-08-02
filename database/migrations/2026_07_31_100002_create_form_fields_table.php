<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One element on a form — an input, a piece of display copy, or a button.
 *
 * `type` is a code from the registry in config('forms.field_types'), never a DB
 * row: a mirrored catalogue drifts the moment someone deploys without reseeding
 * and the builder would offer settings the compiler cannot read.
 *
 * `key` is the machine name. It is the answer key in form_submissions.data, the
 * CSV column header and the webhook mapping source, so it is unique per form and
 * frozen once submissions exist.
 *
 * TWO KINDS OF JSON HERE, and they must not be confused: settings/validation are
 * ordinary array casts, while label/placeholder/value/content are translatable
 * and belong in $translatable. A translatable key that also appears in $casts
 * fights the trait and loses.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_fields', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('type', 32);
            $table->string('key');
            $table->unsignedInteger('order')->default(0);

            $table->boolean('is_required')->default(false);
            $table->boolean('is_unique')->default(false);

            $table->json('settings')->nullable();
            $table->json('validation')->nullable();

            $table->string('css_id')->nullable();
            $table->string('css_class')->nullable();

            $table->json('label')->nullable();
            $table->json('placeholder')->nullable();
            $table->json('value')->nullable();
            $table->json('content')->nullable();

            $table->ulid('form_id');
            $table->foreign('form_id')->references('id')->on('forms')->cascadeOnDelete();

            $table->ulid('form_page_id');
            $table->foreign('form_page_id')->references('id')->on('form_pages')->cascadeOnDelete();

            $table->ulid('target_form_page_id')->nullable();
            $table->foreign('target_form_page_id')->references('id')->on('form_pages')->nullOnDelete();

            $table->unique(['form_id', 'key']);
            $table->index(['form_page_id', 'order']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_fields');
    }
};
