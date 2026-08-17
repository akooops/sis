<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Repeatable groups: a `group` element owns child fields, and this is the only
 * column that makes it possible.
 *
 * CHILDREN ARE REAL ROWS, not JSON packed into the group's settings. A child is
 * an ordinary form_fields row and therefore keeps everything an element already
 * has for free — the builder's card and inspector, the registry's settings and
 * validation schema, and translatable label/placeholder via the same
 * mergeTranslations path. Packing them into settings would have forked all four.
 *
 * THE ANSWER STAYS FLAT. form_submissions.data remains a `key => value` map with
 * exactly one entry per top-level capturing field; a group's entry simply holds a
 * list of objects keyed by child key:
 *
 *     data['education'] = [{institution: …, degree: …}, …]
 *
 * So nothing downstream has to learn about parentage to read a submission — only
 * to render or validate one.
 *
 * `order` is scoped to the PARENT for a child (position inside the group) and to
 * the page for a top-level field, which is why the new index pairs it with
 * parent_form_field_id rather than replacing the page one.
 *
 * cascadeOnDelete is a backstop against orphans only: FormBuilderController still
 * deletes children explicitly, innermost first, so their observers fire and any
 * media they own is freed. A cascade fires no model events.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->ulid('parent_form_field_id')->nullable()->after('form_page_id');

            $table->index(['parent_form_field_id', 'order']);
            $table->foreign('parent_form_field_id')->references('id')->on('form_fields')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->dropForeign(['parent_form_field_id']);
            $table->dropIndex(['parent_form_field_id', 'order']);
            $table->dropColumn('parent_form_field_id');
        });
    }
};
