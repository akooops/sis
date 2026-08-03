<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The configurable settings registry, seeded from config('settings.settings').
 *
 * Every column but `value` is metadata owned by that config file and refreshed
 * on every reseed; `value` is the one an admin ever writes, which is why the
 * module has no store and no destroy. A row is DESCRIBED in code and ANSWERED in
 * the admin.
 *
 * `type` names the shape of `value` and `is_multiple` says whether there is one
 * of them or a list — one flag rather than a second set of type codes.
 * `model_type` is an App\Enums\MorphType alias (never a class name) and stays
 * null unless the type is `model`; `options` holds the choices for `select` and
 * is null everywhere else.
 *
 * `value` is json rather than a typed column because the shape differs per row:
 * it legitimately holds a scalar for a single-valued setting and an array for a
 * multiple one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('group')->index();
            $table->string('key');

            $table->string('type', 32);
            $table->boolean('is_multiple')->default(false);

            $table->string('model_type', 32)->nullable();
            $table->json('options')->nullable();

            $table->string('name');
            $table->string('description')->nullable();

            $table->json('value')->nullable();

            $table->unsignedInteger('order')->default(0);

            $table->unique(['group', 'key']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
