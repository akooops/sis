<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('name');
            $table->string('url');

            // Display position, ascending. Set by the reorder endpoint, never by
            // the create/edit form — a new partner just goes on the end.
            $table->unsignedInteger('order')->default(0)->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
