<?php

namespace App\Observers;

use App\Models\Language;
use App\Services\Translations\TranslationService;
use App\Services\Uploads\UploadService;
use Illuminate\Database\Eloquent\Model;

class LanguageObserver extends BaseObserver
{
    public function __construct(protected TranslationService $translations) {}

    /**
     * A disabled default is unreachable — the picker only offers enabled languages.
     * Coerce rather than 422: the intent is obvious.
     */
    public function saving(Language $language): void
    {
        if ($language->is_default) {
            $language->is_enabled = true;
        }
    }

    /**
     * Audit the create, then give the language its folder. Runs for the seeder too,
     * so reseeding restores a missing lang/{code}/. The param stays Model — PHP
     * forbids narrowing the parent signature.
     */
    public function created(Model $model): void
    {
        parent::created($model);

        $this->translations->ensureLocale($model->code);
    }

    /**
     * The files ARE the translations, so a code rename moves them. Deliberately NOT
     * symmetric with deleting(), which leaves them behind.
     */
    public function updated(Model $model): void
    {
        parent::updated($model);

        $changes = $model->getChanges();

        if (array_key_exists('code', $changes)) {
            $this->translations->renameLocale($model->getOriginal('code'), $changes['code']);
        }
    }

    /**
     * Free the flag rather than destroying the file. lang/{code}/ is untouched: the
     * translations outlive the row, so re-adding the code picks them back up.
     */
    public function deleting(Language $language): void
    {
        Language::forgetCodes();

        UploadService::freeModel($language);
    }

    /**
     * Exactly one default. A builder update fires no events, so there is no
     * recursion and no audit row per demoted language.
     */
    public function saved(Model $model): void
    {
        // Changes what the translated-search filter and every translatable DTO resolve.
        Language::forgetCodes();

        if ($model->is_default) {
            Language::query()->whereKeyNot($model->getKey())->update(['is_default' => false]);
        }
    }

    protected function logName(): string
    {
        return 'languages';
    }

    /**
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return ['name', 'code', 'is_default', 'is_rtl', 'is_enabled'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->name];
    }
}
