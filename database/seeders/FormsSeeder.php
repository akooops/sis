<?php

namespace Database\Seeders;

use App\Models\Form;
use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Models\FormPage;
use App\States\Form\Published;
use Illuminate\Database\Seeder;

/**
 * Forms the app itself resolves by slug. `is_system` freezes the slug and blocks
 * the delete, and UpdateBuilderData::withValidator() (via Form::isLocked()) blocks
 * changes to the pages and fields — the settings stay editable.
 *
 * config('forms.system') is intentionally empty: the capability ships, the
 * content doesn't. Add an entry when you know a slug the code depends on.
 *
 * firstOrCreate for the form, matching PagesSeeder: a form row is authored
 * content and a reseed must never clobber copy an admin has since written.
 * Structure is only created when the form itself is new, for the same reason —
 * re-running this must not resurrect a page someone deliberately removed, nor
 * duplicate one.
 *
 * Runs AFTER LanguagesSeeder, because Language::defaultCode() memoises statically
 * for the process and would cache a miss if no language row existed yet.
 */
class FormsSeeder extends Seeder
{
    public function run(): void
    {
        $forms = config('forms.system', []);

        if ($forms === []) {
            return;
        }

        $default = \App\Models\Language::defaultCode();

        foreach ($forms as $definition) {
            $form = Form::firstOrCreate(
                ['slug' => $definition['slug']],
                [
                    'name' => $definition['name'],
                    'title' => [$default => $definition['title'] ?? $definition['name']],
                    'confirmation_message' => [$default => $definition['confirmation_message'] ?? 'Thank you. Your response has been recorded.'],
                    'status' => Published::class,
                    'published_at' => now(),
                    'is_system' => true,
                ],
            );

            // An existing form adopted into the system set: assert the lock only.
            if (! $form->wasRecentlyCreated) {
                if (! $form->is_system) {
                    $form->forceFill(['is_system' => true])->save();
                }

                continue;
            }

            $this->buildStructure($form, $definition, $default);
        }
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    protected function buildStructure(Form $form, array $definition, string $default): void
    {
        foreach ($definition['pages'] ?? [['name' => 'Page 1', 'fields' => $definition['fields'] ?? []]] as $pageIndex => $pageDefinition) {
            $page = FormPage::create([
                'form_id' => $form->id,
                'name' => $pageDefinition['name'] ?? 'Page '.($pageIndex + 1),
                'order' => $pageIndex,
                'is_interstitial' => (bool) ($pageDefinition['is_interstitial'] ?? false),
                'title' => [$default => $pageDefinition['title'] ?? ''],
            ]);

            foreach ($pageDefinition['fields'] ?? [] as $fieldIndex => $fieldDefinition) {
                $field = FormField::create([
                    'form_id' => $form->id,
                    'form_page_id' => $page->id,
                    'type' => $fieldDefinition['type'],
                    'key' => $fieldDefinition['key'],
                    'order' => $fieldIndex,
                    'is_required' => (bool) ($fieldDefinition['is_required'] ?? false),
                    'settings' => $fieldDefinition['settings'] ?? null,
                    'validation' => $fieldDefinition['validation'] ?? null,
                    'label' => [$default => $fieldDefinition['label'] ?? ''],
                    'content' => [$default => $fieldDefinition['content'] ?? ''],
                ]);

                foreach ($fieldDefinition['options'] ?? [] as $optionIndex => $option) {
                    FormFieldOption::create([
                        'form_field_id' => $field->id,
                        'value' => $option['value'],
                        'order' => $optionIndex,
                        'label' => [$default => $option['label'] ?? $option['value']],
                    ]);
                }
            }
        }
    }
}
