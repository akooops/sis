<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One way to reach the school — a phone, an email, a postal address, a social
 * profile.
 *
 * `type` is a code from the registry in config('contacts.types'), never a DB
 * row: a mirrored catalogue drifts the moment someone deploys without reseeding
 * and the form would offer a type the server has no rule for (the same call
 * form_fields makes).
 *
 * `name` is the internal filing label, `title` the translated public one.
 * `value` holds the machine-readable half — an E164 number, an email address, a
 * URL — and is NULL for the address type, whose text is translated in `address`.
 *
 * The remaining columns are per-type extras and stay null everywhere else:
 * `platform` belongs to social, map_url/latitude/longitude to an address. The
 * controller blanks the ones the chosen type does not use, so a retyped row
 * never keeps the old type's data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_details', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('type', 32)->index();

            $table->string('name');
            $table->json('title')->nullable();

            // 2048 so a social profile URL fits: the url rule allows that much and
            // a default varchar(255) would truncate it.
            $table->string('value', 2048)->nullable();

            $table->json('address')->nullable();

            // A code from config('contacts.platforms'), not a display name: the
            // icon and the label both hang off that entry.
            $table->string('platform', 32)->nullable();

            $table->string('map_url', 2048)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->unsignedInteger('order')->default(0)->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_details');
    }
};
