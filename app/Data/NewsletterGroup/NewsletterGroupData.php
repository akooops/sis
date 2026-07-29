<?php

namespace App\Data\NewsletterGroup;

use App\Models\NewsletterGroup;
use Spatie\LaravelData\Data;

/** `subscribers_count` comes from withCount — the members are their own endpoint. */
class NewsletterGroupData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $code,
        public bool $is_default,
        /** @var array<string, string|null> */
        public array $title,
        /** @var array<string, string|null> */
        public array $description,
        public int $subscribers_count,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(NewsletterGroup $group): self
    {
        return new self(
            id: $group->id,
            name: $group->name,
            code: $group->code,
            is_default: $group->is_default,
            title: $group->enabledTranslations('title'),
            description: $group->enabledTranslations('description'),
            subscribers_count: $group->subscribers_count ?? 0,
            created_at: $group->created_at?->toIso8601String(),
            updated_at: $group->updated_at?->toIso8601String(),
        );
    }
}
