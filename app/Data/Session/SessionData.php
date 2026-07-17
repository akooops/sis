<?php

namespace App\Data\Session;

use App\Data\User\UserData;
use App\Models\Session;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

/**
 * Output DTO for a login session.
 *
 * No `session_id` and no `payload`, ever. The session id is the cookie value:
 * handing it to a client would let them assume the session. `id` is our own ULID
 * and is what the revoke routes take.
 *
 * `is_current` marks the session making this very request, so the UI can say so
 * before you revoke the device you are sitting at.
 */
class SessionData extends Data
{
    public function __construct(
        public string $id,
        public ?string $user_id,
        public ?string $ip_address,
        public ?string $user_agent,
        public ?string $last_activity,
        public bool $is_current,
        public bool $is_expired,
        public ?string $created_at,
        public ?string $updated_at,
        public Lazy|array|null $user,
    ) {}

    public static function fromModel(Session $session): self
    {
        return new self(
            id: $session->id,
            user_id: $session->user_id,
            ip_address: $session->ip_address,
            user_agent: $session->user_agent,
            last_activity: $session->last_activity?->toIso8601String(),
            is_current: $session->is_current,
            is_expired: $session->isExpired(),
            created_at: $session->created_at?->toIso8601String(),
            updated_at: $session->updated_at?->toIso8601String(),
            user: Lazy::whenLoaded('user', $session, fn () => UserData::from($session->user))
        );
    }
}
