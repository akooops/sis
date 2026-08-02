<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Addresses a form refuses, checked on the same guard as the country block.
 *
 * `value` is either a single address (v4 or v6) or a CIDR range; `is_cidr` says
 * which, so the matcher does not have to re-parse every row on every request.
 * 45 chars covers an IPv6 address, and the /nn suffix fits in the same column.
 *
 * Only as trustworthy as request()->ip(), which behind a proxy means
 * App\Http\Middleware\TrustProxies must be configured — otherwise this blocks
 * the proxy or nobody.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_blocked_ips', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('value', 64);
            $table->boolean('is_cidr')->default(false);
            $table->string('note')->nullable();

            $table->ulid('form_id');
            $table->foreign('form_id')->references('id')->on('forms')->cascadeOnDelete();

            $table->unique(['form_id', 'value']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_blocked_ips');
    }
};
