<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A posting's vector, in the SAME space as a candidate's.
 *
 * That is the whole point: embed both with one model and "which pool does this
 * vacancy draw from?" becomes a cosine against the centroids the candidates were
 * already clustered into. Clustering the postings separately would produce a
 * second taxonomy that has to be kept in step with the first, and nothing would
 * keep it there.
 *
 * JSON because MySQL has no vector type — see candidates.embedding, which carries
 * the same 256 dimensions for the same reason.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_offers', function (Blueprint $table) {
            $table->json('embedding')->nullable()->after('custom_css');
            $table->dateTime('embedded_at')->nullable()->after('embedding');
        });
    }

    public function down(): void
    {
        Schema::table('job_offers', function (Blueprint $table) {
            $table->dropColumn(['embedding', 'embedded_at']);
        });
    }
};
