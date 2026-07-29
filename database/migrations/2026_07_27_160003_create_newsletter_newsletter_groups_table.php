<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_newsletter_groups', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->ulid('newsletter_id');
            $table->foreign('newsletter_id', 'nng_newsletter_fk')->references('id')->on('newsletters')->cascadeOnDelete();

            $table->ulid('newsletter_group_id');
            $table->foreign('newsletter_group_id', 'nng_group_fk')->references('id')->on('newsletter_groups')->cascadeOnDelete();

            $table->unique(['newsletter_id', 'newsletter_group_id'], 'nng_newsletter_group_unique');

            $table->index('newsletter_id', 'nng_newsletter_index');
            $table->index('newsletter_group_id', 'nng_group_index');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_newsletter_groups');
    }
};
