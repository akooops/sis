<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A choice on a select, radio or checkbox group.
 *
 * `value` is what gets stored and exported and is deliberately NOT translatable
 * — translating it would make the same answer read as several different values
 * across locales and break every downstream filter. `label` is the translatable
 * half the visitor actually sees.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_field_options', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('value');
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_default')->default(false);

            $table->json('label')->nullable();
            
            $table->ulid('form_field_id');
            $table->foreign('form_field_id')->references('id')->on('form_fields')->cascadeOnDelete();

            $table->unique(['form_field_id', 'value']);
            $table->index(['form_field_id', 'order']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_field_options');
    }
};
