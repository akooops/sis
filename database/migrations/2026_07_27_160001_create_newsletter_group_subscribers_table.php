<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_group_subscribers', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('name')->nullable();

            $table->string('signature', 64)->unique();
            $table->string('email');

            $table->boolean('is_active')->default(true);
            $table->dateTime('subscribed_at')->nullable();

            $table->ulid('newsletter_group_id');
            $table->foreign('newsletter_group_id')->references('id')->on('newsletter_groups')->restrictOnDelete();

            $table->unique(['newsletter_group_id', 'email']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_group_subscribers');
    }
};
