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
     * A default language that is disabled is incoherent — the Translations picker
     * only offers enabled languages, so it would be unreachable. Coerce rather
     * than 422: the intent is obvious.
     */
    public function saving(Language $language): void
    {
        if ($language->is_default) {
            $language->is_enabled = true;
        }
    }

    /**
     * Audit the create (parent), then give the language its folder. This runs for
     * the seeder too, so reseeding always restores a missing lang/{code}/.
     * The parameter stays Model — PHP forbids narrowing BaseObserver::created().
     */
    public function created(Model $model): void
    {
        parent::created($model);

        $this->translations->ensureLocale($model->code);
    }

    /**
     * The files ARE the translations, so renaming the code moves them. Note this
     * is deliberately NOT symmetric with deleting(), which leaves them behind.
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
     * Free the flag back into the reusable pool rather than destroying the file.
     * lang/{code}/ is untouched: the translations outlive the row, so re-adding
     * the code picks them straight back up.
     */
    public function deleting(Language $language): void
    {
        Language::forgetCodes();

        UploadService::freeModel($language);
    }

    /**
     * Exactly one default. A builder update fires no model events, so there is no
     * observer recursion and no audit row per demoted language — the same reason
     * User::syncRoles() removes with a builder delete.
     */
    public function saved(Model $model): void
    {
        // Enabling, disabling or re-defaulting a language changes what the
        // translated-search filter and every translatable DTO resolve.
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
