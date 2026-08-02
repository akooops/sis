<?php

namespace App\Data\Form;

use App\Models\FormBlockedIp;
use Spatie\LaravelData\Data;

class FormBlockedIpData extends Data
{
    public function __construct(
        public string $id,
        public string $form_id,
        public string $value,
        public bool $is_cidr,
        public ?string $note,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(FormBlockedIp $blockedIp): self
    {
        return new self(
            id: $blockedIp->id,
            form_id: $blockedIp->form_id,
            value: $blockedIp->value,
            is_cidr: (bool) $blockedIp->is_cidr,
            note: $blockedIp->note,
            created_at: $blockedIp->created_at?->toIso8601String(),
            updated_at: $blockedIp->updated_at?->toIso8601String(),
        );
    }
}
