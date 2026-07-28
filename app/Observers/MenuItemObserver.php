<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class MenuItemObserver extends BaseObserver
{
    protected function logName(): string
    {
        return 'menu-items';
    }

    /**
     * Both are set by dragging, not by editing. Reorder is a builder update so it
     * never reaches an observer, but a create would still record them — noise on
     * fields nobody typed.
     *
     * @return array<int, string>
     */
    protected function ignored(): array
    {
        return ['order', 'parent_id'];
    }

    /**
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return ['name', 'menu_id', 'url'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->name];
    }
}
