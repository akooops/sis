<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A PERSON, not an application.
 *
 * One human who has applied at least once. The split is what makes "show this
 * candidate's other good matches" and "show this posting's good candidates" the
 * same query read from two ends — and it is what stops someone who applies to
 * three postings becoming three unrelated records nobody can tell apart.
 *
 * IDENTITY IS EMAIL OR PHONE. Both are unique; the projector looks a person up
 * by either before creating one. Phone is stored E164 (PhoneFormatter::e164, the
 * same normalisation PhoneType::store applies at submit) so `+213 555 123 456`
 * and `+213555123456` are the same person rather than two.
 *
 * Phone is nullable because a form may not ask for one, and MySQL treats every
 * NULL in a unique index as distinct — which is the behaviour we want here (many
 * candidates with no phone must all be allowed) and exactly the behaviour that
 * made a nullable job_offer_id unusable for dedup on job_applications.
 *
 * `cv_media_id` is a POINTER, not ownership. The file stays a form attachment,
 * owned by its FormSubmission, scanned and reachable only through a signed route
 * — this column just says which one is current. nullOnDelete so losing the file
 * never takes the person with it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone', 32)->nullable();
            $table->text('address')->nullable();

            /* Nationality. A row in countries, not a free string, so the AI and
               the filters both read one canonical value. */
            $table->ulid('country_id')->nullable();
            $table->foreign('country_id')->references('id')->on('countries')->nullOnDelete();

            $table->ulid('cv_media_id')->nullable();
            $table->foreign('cv_media_id')->references('id')->on('media')->nullOnDelete();

            /* AI. `ai_comment` is the candidate-level summary an admin reads;
               per-posting scores live on candidate_matches, not here. */
            $table->text('ai_comment')->nullable();
            $table->dateTime('summarised_at')->nullable();

            /*
             * The embedding, as JSON, because MySQL has no vector type. 256
             * dimensions (requested from the embedding API rather than truncated)
             * keeps a few thousand candidates clusterable in PHP in seconds.
             */
            $table->json('embedding')->nullable();
            $table->dateTime('embedded_at')->nullable();

            $table->unique('email');
            $table->unique('phone');
            $table->index('country_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
