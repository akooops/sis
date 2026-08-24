<?php

namespace App\Data\FacilityAlbum;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/** A bulk attach: PivotDrawer posts every id the multi-select is holding. */
class StoreFacilityAlbumData extends Data
{
    /**
     * @param  array<int, string>  $albums
     */
    public function __construct(public array $albums) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'albums' => ['required', 'array'],
            'albums.*' => ['string', 'exists:albums,id'],
        ];
    }
}
