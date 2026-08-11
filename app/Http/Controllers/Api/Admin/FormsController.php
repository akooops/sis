<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Form\FormData;
use App\Data\Form\StoreFormData;
use App\Data\Form\UpdateFormData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Category;
use App\Models\Form;
use App\Models\FormPage;
use App\Models\Language;
use App\Services\Uploads\UploadService;
use App\States\Form\FormStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\ModelStates\Exceptions\TransitionNotFound;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/** Forms. Web\Admin\PagesController is unrelated — it is the Inertia shell. */
class FormsController extends ApiController
{
    public function index(): JsonResponse
    {
        $forms = QueryBuilder::for(Form::class)
            ->with(['media', 'category'])
            ->withCount(['pages', 'fields'])
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('category_id'),
                AllowedFilter::exact('is_system'),
                // "Which forms notify this group?" — the destination of the
                // Notification Groups page's drill-through, so the link carries
                // the filter instead of dropping the admin into every form.
                AllowedFilter::exact('notification_group_id', 'notificationGroups.id'),
                $this->searchTranslations(
                    ['id', 'name', 'slug'],
                    ['title', 'description'],
                    Language::enabledCodes(),
                ),
            ])
            ->allowedSorts(['id', 'name', 'slug', 'status', 'submissions_count', 'published_at', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(FormData::collect($forms, PaginatedDataCollection::class), 'Forms retrieved successfully');
    }

    public function show(Form $form): JsonResponse
    {
        $form->loadCount(['pages', 'fields']);

        return $this->respond(
            FormData::from($form->load(['category', 'captchaIntegration'])),
            'Form retrieved successfully',
        );
    }

    public function store(StoreFormData $data): JsonResponse
    {
        $default = Language::defaultCode();

        // A form with no page cannot be built or rendered, so the first one is
        // created with it rather than left for the builder to discover.
        $form = DB::transaction(function () use ($data, $default) {
            $form = Form::create([
                'name' => $data->name,
                'slug' => $data->slug,
                'category_id' => $data->category_id ?? Category::default()?->id,
                'title' => [$default => $data->title],
                'description' => [$default => $data->description],
                'content' => [$default => $data->content],
                'confirmation_message' => [$default => $data->confirmation_message ?: 'Thank you. Your response has been recorded.'],
                'status' => FormStatus::resolveStateClass($data->status),
                'published_at' => $data->status === 'published' ? now() : $data->published_at,
                'css_url' => $data->css_url,
                // Already stripped of anything that could close the <style> it
                // ends up in — StoreFormData uses SanitisesCustomCss.
                'custom_css' => $data->custom_css,

                // Every setting is settable here, not only on edit: a form that
                // could be published before its limits and its spam defences
                // existed was live and unguarded for exactly that long.
                'is_limited' => $data->is_limited,
                'submissions_limit' => $data->is_limited ? $data->submissions_limit : null,
                'is_user_limited' => $data->is_user_limited,
                'per_user_limit' => $data->is_user_limited ? $data->per_user_limit : null,
                'per_user_limit_by' => $data->per_user_limit_by,

                'is_spam_filtered' => $data->is_spam_filtered,
                'min_submit_seconds' => $data->min_submit_seconds,
                'is_captcha_enabled' => $data->is_captcha_enabled,
                'is_ip_stored' => $data->is_ip_stored,

                'confirmation_type' => $data->confirmation_type,
                'redirect_url' => $data->confirmation_type === 'redirect' ? $data->redirect_url : null,

                'captcha_integration_id' => $data->is_captcha_enabled ? $data->captcha_integration_id : null,
            ]);

            FormPage::create([
                'form_id' => $form->id,
                'name' => 'Page 1',
                'order' => 0,
                'title' => [$default => ''],
            ]);

            return $form;
        });

        // Required by StoreFormData, so there is nothing to guard here.
        UploadService::attach($data->thumbnail, $form, Form::THUMBNAIL_COLLECTION);

        $form = $form->fresh()->loadCount(['pages', 'fields']);

        return $this->respond(
            FormData::from($form->load(['category', 'captchaIntegration'])),
            'Form created successfully',
            201,
        );
    }

    public function update(UpdateFormData $data, Form $form): JsonResponse
    {
        // mergeTranslations: the form only carries the enabled locales.
        $form->update($form->mergeTranslations(
            Arr::except($data->toArray(), ['status', 'thumbnail', 'published_at']),
        ));

        $target = FormStatus::resolveStateClass($data->status);

        if (! $form->status instanceof $target) {
            try {
                $form->status->transitionTo($target);
            } catch (TransitionNotFound) {
                throw ValidationException::withMessages([
                    'status' => "A {$form->status->getValue()} form cannot become {$data->status}.",
                ]);
            }
        }

        $form->published_at = $data->status === 'published'
            ? ($form->published_at ?? now())
            : $data->published_at;
        $form->save();

        if (! $data->thumbnail instanceof Optional && $data->thumbnail) {
            UploadService::attach($data->thumbnail, $form, Form::THUMBNAIL_COLLECTION);
        }

        $form = $form->fresh()->loadCount(['pages', 'fields']);

        return $this->respond(
            FormData::from($form->load(['category', 'captchaIntegration'])),
            'Form updated successfully',
        );
    }

    public function destroy(Form $form): JsonResponse
    {
        if ($form->is_system) {
            throw ValidationException::withMessages([
                'is_system' => "The {$form->name} form ships with the app and cannot be deleted. Set it to hidden instead.",
            ]);
        }

        // A form that has collected anything CANNOT be deleted, and there is no
        // override. Submissions are the record the form exists to collect,
        // nothing in this app deletes a submission, and there are no soft
        // deletes — so a cascade here would destroy the collected record
        // irreversibly. Export it and hide the form instead.
        if ($form->submissions()->exists()) {
            $submissions = $form->submissions()->count();

            throw ValidationException::withMessages([
                'submissions' => "This form has {$submissions} submission(s) and cannot be deleted. Submissions are the record it exists to collect — export them and set the form to hidden instead.",
            ]);
        }

        $form->delete();

        return $this->respond(null, 'Form deleted successfully');
    }
}
