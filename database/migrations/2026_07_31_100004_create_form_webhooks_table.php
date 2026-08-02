<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An outbound POST fired after a submission is committed.
 *
 * The body is not configurable: every delivery sends the submission id, the form
 * slug, the submitted time and the whole answer set under `data`. Reshaping that
 * payload is the receiving system's job, not this one's.
 *
 * `last_delivered_at` is the ONLY delivery column. There is no status code and
 * no failure counter: both are outcome detail that belongs in the `integrations`
 * log next to the response body, and a column would be a staler second copy that
 * an admin could sort by and misread.
 *
 * `auth_config` holds the bearer token / static headers and is encrypted. Like
 * integrations.config it must ALSO be $hidden on the model, in the observer's
 * ignored() and absent from the read DTO — miss any one of the three and the
 * secret surfaces in an activity diff or an API response.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_webhooks', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('name');
            $table->string('url', 2048);
            $table->string('method', 8)->default('POST');
            $table->boolean('is_enabled')->default(true);

            $table->string('auth_type', 16)->default('none'); // none | headers | bearer
            $table->text('auth_config')->nullable();

            $table->dateTime('last_delivered_at')->nullable();

            $table->ulid('form_id');
            $table->foreign('form_id')->references('id')->on('forms')->cascadeOnDelete();

            $table->index(['form_id', 'is_enabled']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_webhooks');
    }
};
