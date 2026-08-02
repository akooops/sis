<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A page of a form. Every form has at least one; a "page break" in the builder
 * is simply the next row here.
 *
 * A page is a real row rather than a break-type field because its title is
 * translatable and a button has to be able to target it by id.
 *
 * `is_interstitial` marks an instruction page — one you enter from a button and
 * return from, rather than a step in the linear sequence. Linear navigation
 * skips these, so they never appear in the step count or the progress bar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_pages', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('name');
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_interstitial')->default(false);

            $table->string('css_id')->nullable();
            $table->string('css_class')->nullable();

            $table->json('title')->nullable();

            $table->ulid('form_id');
            $table->foreign('form_id')->references('id')->on('forms')->cascadeOnDelete();

            $table->index(['form_id', 'order']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_pages');
    }
};
