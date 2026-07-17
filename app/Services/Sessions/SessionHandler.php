<?php

namespace App\Services\Sessions;

use Illuminate\Database\QueryException;
use Illuminate\Session\DatabaseSessionHandler as BaseSessionHandler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * The adapter between Laravel's session table and ours.
 *
 * Laravel keys a session row by PHP's session id and writes `last_activity` as
 * unix seconds with no timestamps. This app keys everything by ULID and dates
 * everything with datetimes, so the translation lives here rather than leaking a
 * foreign shape into App\Models\Session:
 *
 *   Laravel                          ours
 *   id = <php session id>       ->   id = ULID, session_id = <php session id>
 *   last_activity = 1752754…    ->   last_activity = 2026-07-17 12:28:02
 *   (no timestamps)             ->   created_at / updated_at
 *
 * Registered over the built-in `database` driver in AppServiceProvider —
 * SessionManager::callCustomCreator wraps whatever this returns in a Store, so
 * encryption and cookie config keep working untouched.
 */
class SessionHandler extends BaseSessionHandler
{
    /**
     * {@inheritdoc}
     */
    public function read($sessionId): string|false
    {
        // Parent does find($sessionId) — a primary-key lookup. Ours is keyed by
        // ULID, so the session id is a column like any other.
        $session = (object) $this->getQuery()->where('session_id', $sessionId)->first();

        if ($this->expired($session)) {
            $this->exists = true;

            return '';
        }

        if (isset($session->payload)) {
            $this->exists = true;

            return base64_decode($session->payload);
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function expired($session)
    {
        return isset($session->last_activity)
            && Carbon::parse($session->last_activity)->lt(Carbon::now()->subMinutes($this->minutes));
    }

    /**
     * {@inheritdoc}
     */
    protected function performInsert($sessionId, $payload)
    {
        try {
            return $this->getQuery()->insert($payload + [
                'id' => (string) Str::ulid(),
                'session_id' => $sessionId,
                'created_at' => $now = Carbon::now(),
                'updated_at' => $now,
            ]);
        } catch (QueryException) {
            // Raced by a parallel request for the same session — the unique index
            // on session_id is what makes that a collision rather than a double row.
            $this->performUpdate($sessionId, $payload);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function performUpdate($sessionId, $payload)
    {
        return $this->getQuery()
            ->where('session_id', $sessionId)
            ->update($payload + ['updated_at' => Carbon::now()]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultPayload($data)
    {
        $payload = parent::getDefaultPayload($data);

        // Parent writes time() — an int into what is now a datetime column.
        $payload['last_activity'] = Carbon::createFromTimestamp($payload['last_activity']);

        return $payload;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId): bool
    {
        $this->getQuery()->where('session_id', $sessionId)->delete();

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * $lifetime is in seconds. Laravel calls this on a `session.lottery` roll
     * (2% of requests), which is not a schedule — App\Models\Session is Prunable
     * so `model:prune` clears them on a timetable instead.
     */
    public function gc($lifetime): int
    {
        return $this->getQuery()
            ->where('last_activity', '<=', Carbon::now()->subSeconds($lifetime))
            ->delete();
    }
}
