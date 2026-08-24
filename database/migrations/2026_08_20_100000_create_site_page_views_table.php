<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The public site's page-view log. ONE ROW PER PAGE VIEW, INSERT ONLY.
     *
     * Nothing in this table is ever updated. That is the whole design: no
     * session row to find first, no idle window, no read-modify-write, and so
     * no concurrency to reason about. One page view, one row, one statement.
     *
     * It is NOT telemetry. There is no scroll depth, no focus order, no
     * keystroke timing and no beacon — the visitor's browser runs no extra
     * JavaScript for this. Google Analytics carries the deep reporting; this
     * exists so the admin dashboard can answer "how busy are we, and what are
     * people reading" without leaving the app.
     */
    public function up(): void
    {
        Schema::create('site_page_views', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->dateTime('viewed_at');

            /*
             * WHAT WAS READ — route name plus slug, never the path.
             *
             * routes/web.php registers the site twice, prefixed and unprefixed,
             * so /contact, /en/contact and /ar/contact are one page under two
             * route names. RouteName::normalise() strips the group prefix so
             * all of them group into a single row in every report; `path` is
             * kept for a human reading one row and is never grouped on.
             *
             * `slug` is NOT NULL DEFAULT '' rather than nullable so that "no
             * slug" has exactly one spelling — a listing page and the home page
             * must not arrive as NULL here and as '' there.
             */
            $table->string('route_name', 64);
            $table->string('slug', 191)->default('');
            $table->string('path', 255)->default('/');
            $table->char('locale', 5)->nullable();

            /*
             * WHO — two one-way hashes, never the thing they hash.
             *
             * These are what turn a page-view log into visit and visitor counts
             * with no extra storage: count(*) is page views,
             * count(distinct visit_key) is visits, count(distinct visitor_key)
             * is visitors, and all three come off one scan.
             *
             * visit_key hashes the PHP session id, which IS the cookie value and
             * therefore a credential — Session::$hidden hides it for exactly
             * this reason, so it is never copied here in the clear. visitor_key
             * is an ip+user-agent proxy: it under-counts a computer lab behind
             * one NAT and over-counts anyone moving from Wi-Fi to cellular. The
             * dashboard says "approximate" because of that.
             */
            $table->char('visit_key', 64);
            $table->char('visitor_key', 64);

            /* Where they came from. */
            $table->string('entry_point', 16)->default('direct');
            $table->string('referrer_host', 191)->nullable();
            $table->string('utm_campaign', 191)->nullable();

            /* What they were using. */
            $table->char('country_code', 2)->nullable();
            $table->string('device_type', 16)->nullable();
            $table->string('browser', 64)->nullable();
            $table->string('os', 64)->nullable();

            /*
             * Recorded, then excluded from every dashboard query and surfaced as
             * one footnote number. Kept rather than dropped so "how much of our
             * traffic is crawlers" stays answerable.
             */
            $table->boolean('is_bot')->default(false);

            /*
             * ONE INDEX, DELIBERATELY.
             *
             * Every read is `where is_bot = 0 and viewed_at between ? and ?
             * group by <something>`, and pruning is a range on the same column.
             * This carries the range; the grouping then runs over rows already
             * in memory. An index on route_name or device_type would not help a
             * range-filtered GROUP BY and would tax every single page view.
             */
            $table->index('viewed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_page_views');
    }
};
