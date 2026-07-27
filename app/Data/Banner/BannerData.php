<?php

namespace App\Data\Banner;

use App\Enums\MorphType;
use App\Models\Banner;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\Data;

/**
 * `order` is read-only here — it is written by the reorder endpoint.
 * `linkable_type` is a MorphType alias, never a class name, and `linkable` is
 * the linked record inlined rather than one DTO per linkable model.
 */
class BannerData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public int $order,
        public ?string $url,
        public ?string $linkable_type,
        public ?string $linkable_id,
        /** @var array<string, string|null> */
        public array $title,
        /** @var array<string, string|null> */
        public array $cta,
        public ?string $thumbnail_url,
        public ?string $video_url,
        public ?string $created_at,
        public ?string $updated_at,
        /** @var array<string, string|null>|null */
        public ?array $linkable,
    ) {}

    public static function fromModel(Banner $banner): self
    {
        return new self(
            id: $banner->id,
            name: $banner->name,
            order: $banner->order,
            url: $banner->url,
            linkable_type: MorphType::aliasFor($banner->linkable_type),
            linkable_id: $banner->linkable_id,
            title: $banner->enabledTranslations('title'),
            cta: $banner->enabledTranslations('cta'),
            thumbnail_url: $banner->thumbnail_url,
            video_url: $banner->video_url,
            created_at: $banner->created_at?->toIso8601String(),
            updated_at: $banner->updated_at?->toIso8601String(),
            linkable: static::link($banner->linkable),
        );
    }

    /**
     * name and slug are the two columns all eight linkable models share.
     *
     * @return array<string, string|null>|null
     */
    protected static function link(?Model $model): ?array
    {
        if ($model === null) {
            return null;
        }

        return [
            'type' => MorphType::aliasFor($model->getMorphClass()),
            'id' => $model->getKey(),
            'name' => $model->getAttribute('name'),
            'slug' => $model->getAttribute('slug'),
        ];
    }
}
