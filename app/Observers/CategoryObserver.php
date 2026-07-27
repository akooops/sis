<?php

namespace App\Observers;

use App\Models\Category;
use Illuminate\Database\Eloquent\Model;

class CategoryObserver extends BaseObserver
{
    /**
     * There must always be a default, so switching the flag off is a no-op:
     * change it by promoting another category, which demotes this one in saved().
     * Coerce rather than 422, as LanguageObserver does.
     */
    public function saving(Category $category): void
    {
        if ($category->exists
            && $category->isDirty('is_default')
            && ! $category->is_default
            && ! Category::query()->where('is_default', true)->whereKeyNot($category->getKey())->exists()
        ) {
            $category->is_default = true;
        }
    }

    /**
     * Exactly one default. Only demote when this save actually made it the
     * default — a save that wrote nothing (a stale instance whose flag disagrees
     * with the row) would otherwise demote the real default and leave none.
     *
     * A builder update fires no events, so there is no recursion and no audit row
     * per demoted category.
     */
    public function saved(Model $model): void
    {
        if ($model->is_default && ($model->wasRecentlyCreated || $model->wasChanged('is_default'))) {
            Category::query()
                ->whereKeyNot($model->getKey())
                ->update(['is_default' => false]);
        }
    }

    protected function logName(): string
    {
        return 'categories';
    }

    /**
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return ['name', 'code', 'type', 'is_default'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->name];
    }
}
