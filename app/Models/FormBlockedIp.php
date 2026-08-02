<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An address or range a form refuses.
 *
 * Only as trustworthy as request()->ip(), so behind a proxy this depends on
 * App\Http\Middleware\TrustProxies being configured — otherwise it blocks the
 * proxy or nobody.
 */
class FormBlockedIp extends Model
{
    use HasFactory, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    protected $guarded = ['id'];

    protected $casts = [
        'is_cidr' => 'bool',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    /** Whether an address falls in this entry. */
    public function matches(?string $ip): bool
    {
        if (! is_string($ip) || $ip === '') {
            return false;
        }

        return $this->is_cidr
            ? static::inRange($ip, $this->value)
            : strcasecmp($ip, (string) $this->value) === 0;
    }

    /**
     * CIDR containment for v4 and v6, done on the packed address so the two
     * families share one implementation. A malformed row matches nothing rather
     * than throwing — an admin typo must not take a public form down.
     */
    public static function inRange(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = array_pad(explode('/', $cidr, 2), 2, null);

        $address = @inet_pton($ip);
        $network = @inet_pton((string) $subnet);

        if ($address === false || $network === false || strlen($address) !== strlen($network)) {
            return false;
        }

        $bits = $bits === null ? strlen($address) * 8 : (int) $bits;

        if ($bits < 0 || $bits > strlen($address) * 8) {
            return false;
        }

        $wholeBytes = intdiv($bits, 8);
        $remainder = $bits % 8;

        if ($wholeBytes > 0 && strncmp($address, $network, $wholeBytes) !== 0) {
            return false;
        }

        if ($remainder === 0) {
            return true;
        }

        $mask = chr(0xFF << (8 - $remainder) & 0xFF);

        return ($address[$wholeBytes] & $mask) === ($network[$wholeBytes] & $mask);
    }
}
