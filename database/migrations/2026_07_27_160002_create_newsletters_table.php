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
            $table->string('subject');
            $table->longText('content')->nullable();

            $table->string('status')->default('draft')->index();
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('sent_at')->nullable();

            $table->ulid('integration_id')->nullable();
            $table->foreign('integration_id')->references('id')->on('integrations')->nullOnDelete();

            // What newsletters:send-scheduled reads on every tick.
            $table->index(['status', 'scheduled_at']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletters');
    }
};
