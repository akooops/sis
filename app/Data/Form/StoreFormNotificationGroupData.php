<?php

namespace App\Data\Form;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * There is no update: the link is two foreign keys, so the pair is add/remove.
 *
 * TWO SHAPES, because the same pivot is edited from both ends and neither owns
 * it — the payload names its parent and lists the children being attached:
 *
 *   { form_id, notification_groups: [...] }   from the form's page
 *   { notification_group_id, forms: [...] }   from the group's page
 *
 * Exactly one parent, enforced by required_without + prohibits: sending both
 * would leave "which one is the parent" to whichever branch the controller read
 * first, and a silently ignored half of the payload is worse than a 422.
 *
 * The duplicate check mirrors fng_form_group_unique so re-adding an attached
 * group is a 422 naming the row, not a 500 from the database.
 */
class StoreFormNotificationGroupData extends Data
{
    public function __construct(
        public ?string $form_id = null,
        public ?string $notification_group_id = null,
        /** @var array<int, string>|null */
        public ?array $notification_groups = null,
        /** @var array<int, string>|null */
        public ?array $forms = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(ValidationContext $context): array
    {
        $formId = $context->payload['form_id'] ?? null;
        $groupId = $context->payload['notification_group_id'] ?? null;

        return [
            'form_id' => [
                'nullable', 'string', 'required_without:notification_group_id',
                'prohibits:notification_group_id', Rule::exists('forms', 'id'),
            ],
            'notification_group_id' => [
                'nullable', 'string', 'required_without:form_id',
                Rule::exists('notification_groups', 'id'),
            ],

            // From the form's side: the groups it routes to.
            'notification_groups' => ['array', 'min:1', 'required_with:form_id'],
            'notification_groups.*' => [
                'string',
                // The unique rule reads the DATABASE, so it cannot see the same
                // id listed twice in this payload — that pair would validate and
                // then hit fng_form_group_unique.
                'distinct',
                Rule::exists('notification_groups', 'id'),
                Rule::unique('form_notification_groups', 'notification_group_id')->where('form_id', $formId),
            ],

            // From the group's side: the forms that route to it.
            'forms' => ['array', 'min:1', 'required_with:notification_group_id'],
            'forms.*' => [
                'string',
                'distinct',
                Rule::exists('forms', 'id'),
                Rule::unique('form_notification_groups', 'form_id')->where('notification_group_id', $groupId),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(...$args): array
    {
        return [
            'notification_groups.*.unique' => 'That group is already notified by this form.',
            'notification_groups.*.exists' => 'That notification group no longer exists.',
            'forms.*.unique' => 'That form already notifies this group.',
            'forms.*.exists' => 'That form no longer exists.',
            'form_id.prohibits' => 'Send a form or a notification group as the parent, not both.',
        ];
    }
}
