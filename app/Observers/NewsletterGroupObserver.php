<?php

namespace App\Observers;

use App\Models\NewsletterGroup;
use App\Services\Newsletter\FormOptions;
use Illuminate\Database\Eloquent\Model;

class NewsletterGroupObserver extends BaseObserver
{
    /**
     * There must always be a default, so switching the flag off is a no-op:
     * change it by promoting another list, which demotes this one in saved().
     * Coerce rather than 422, as LanguageObserver does.
     */
    public function saving(NewsletterGroup $group): void
    {
        if ($group->exists
            && $group->isDirty('is_default')
            && ! $group->is_default
            && ! NewsletterGroup::query()->where('is_default', true)->whereKeyNot($group->getKey())->exists()
        ) {
            $group->is_default = true;
        }
    }

    /**
     * Exactly one default. Only demote when this save actually made it the
     * default — a save that wrote nothing (a stale instance whose flag disagrees
     * with the row) would otherwise demote the real default and leave none.
     *
     * A builder update fires no events, so there is no recursion and no audit row
     * per demoted list.
     *
     * THE PICKER IS RE-SYNCED FROM HERE, AFTER THE DEMOTION, AND THAT ORDER IS
     * THE WHOLE POINT. Eloquent fires `created`/`updated` inside
     * performInsert()/performUpdate() and `saved` only later in finishSave() — so
     * syncing from those two ran while TWO rows still carried `is_default`, and
     * FormOptions::options() sorts `is_default` first. The tie fell through to
     * `name`, the option order was written for a two-default world, and the
     * demotion below fires no events to correct it: promoting a list left the OLD
     * default sitting first in the public checkbox list, and it stayed that way
     * until some unrelated edit happened to re-sync.
     */
    public function saved(Model $model): void
    {
        if ($model->is_default && ($model->wasRecentlyCreated || $model->wasChanged('is_default'))) {
            NewsletterGroup::query()
                ->whereKeyNot($model->getKey())
                ->update(['is_default' => false]);
        }

        // Gated on the columns the picker actually shows: `saved` fires on every
        // save including a no-op one, and rewriting every option row for an edit
        // to a description is noise in the audit log.
        if ($model->wasRecentlyCreated || $model->wasChanged(['code', 'title', 'name', 'is_default'])) {
            $this->options()->sync();
        }
    }

    /**
     * A `code` CHANGE IS CARRIED, NOT RE-CREATED. The option's value IS the code
     * (see FormOptions for why), so a rename would otherwise read as one list
     * being retired and another appearing.
     *
     * Here rather than in saved() because it must happen BEFORE the sync, and
     * `updated` fires first — which is the one thing that event ordering is
     * useful for rather than dangerous.
     */
    public function updated(Model $model): void
    {
        parent::updated($model);

        if ($model->wasChanged('code')) {
            $this->options()->rename((string) $model->getOriginal('code'), (string) $model->code);
        }
    }

    /**
     * A retired list stops being offered. Old submissions keep the code they
     * stored, which then points at nothing — acceptable, because the subscriber
     * rows are the record and the submission is only the historical copy of what
     * was said.
     */
    public function deleted(Model $model): void
    {
        parent::deleted($model);

        $this->options()->sync();
    }

    /**
     * WHY THE SIGNUP FORM NEEDS ANY OF THIS. The picker's choices are
     * `form_field_options` rows, and FormsSeeder writes them exactly once —
     * run() skips buildStructure() unless the Form was just created — so without
     * a sync a list an admin adds today would never be offered, and posting its
     * code would fail the membership rule those same rows define.
     *
     * ON THE OBSERVER, NOT IN THE CONTROLLER, so the seeder, a console command
     * and a tinker session are covered too — the controller is one of several
     * ways a group is written, and the one that is easiest to remember is not
     * the one to rely on.
     *
     * Resolved per call: the observer outlives any one request.
     */
    protected function options(): FormOptions
    {
        return app(FormOptions::class);
    }

    protected function logName(): string
    {
        return 'newsletter-groups';
    }

    /**
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return ['name', 'code', 'is_default'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->name];
    }
}
