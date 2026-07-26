<?php

namespace App\Enums;

/**
 * What a category classifies. A category belongs to exactly one type, so the
 * Articles picker only ever offers article categories.
 *
 * Grown by code: add a case here and it appears in the type filter, the form's
 * type select and the validation rule, because all three read values() rather
 * than a hardcoded list.
 */
enum CategoryType: string
{
    case Articles = 'articles';
    case Achievements = 'achievements';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Articles => 'Articles',
            self::Achievements => 'Achievements',
        };
    }
}
