<?php

namespace Database\Seeders;

use App\Models\NewsletterGroup;
use Illuminate\Database\Seeder;

/**
 * The fallback mailing list. A signup that names no list lands here, so there is
 * always somewhere for an address to go.
 *
 * `is_default` is the lock — the controller refuses to delete it and the observer
 * refuses to leave nothing default. Everything else stays editable: rename it,
 * rewrite its copy, translate it.
 *
 * firstOrCreate, like CategoriesSeeder: this is editorial content, so a reseed
 * must not clobber a name an admin changed. Only the lock is re-asserted.
 */
class NewsletterGroupsSeeder extends Seeder
{
    /** One per locale in LanguagesSeeder, so enabling one later needs no edit. */
    protected array $title = [
        'en' => 'General',
        'ar' => 'عام',
        'fr' => 'Général',
        'es' => 'General',
        'de' => 'Allgemein',
        'it' => 'Generale',
        'pt' => 'Geral',
        'ru' => 'Общее',
        'hi' => 'सामान्य',
    ];

    protected array $description = [
        'en' => 'News and announcements for everyone.',
        'ar' => 'الأخبار والإعلانات للجميع.',
        'fr' => 'Actualités et annonces pour tous.',
        'es' => 'Noticias y anuncios para todos.',
        'de' => 'Neuigkeiten und Ankündigungen für alle.',
        'it' => 'Notizie e annunci per tutti.',
        'pt' => 'Notícias e anúncios para todos.',
        'ru' => 'Новости и объявления для всех.',
        'hi' => 'सभी के लिए समाचार और घोषणाएँ।',
    ];

    public function run(): void
    {
        $group = NewsletterGroup::firstOrCreate(
            ['code' => (string) config('newsletter.default_group')],
            [
                'name' => 'General',
                'title' => $this->title,
                'description' => $this->description,
                'is_default' => true,
            ],
        );

        if (! $group->wasRecentlyCreated && ! NewsletterGroup::default()) {
            $group->forceFill(['is_default' => true])->save();
        }
    }
}
