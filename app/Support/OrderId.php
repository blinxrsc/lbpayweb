<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Generates order references that cannot be guessed and cannot collide.
 *
 * Old format: ORD-M-20261014211500-42 (timestamp + customer id).
 *  - Guessable: anyone who knows a customer id and roughly when they paid
 *    could reconstruct it, which mattered because the return URL used to
 *    auto-login whoever presented a valid order id.
 *  - Collides: two guests paying in the same second both produced
 *    ORD-M-<same second>-guest, and order_id is UNIQUE → 500 error.
 *
 * New format: ORD-M-01JA2Z7Q8K3V6X9C4B5N7M2P1R (26-char ULID).
 * ULIDs are time-sortable (so admin lists still read in order), carry
 * 80 bits of randomness, and fit comfortably inside Fiuu's orderid limit.
 */
final class OrderId
{
    public const DEVICE_GATEWAY = 'M'; // machine payment through Fiuu
    public const DEVICE_WALLET  = 'W'; // machine payment from e-wallet
    public const TOPUP          = 'T'; // e-wallet top-up through Fiuu

    public static function make(string $kind): string
    {
        return 'ORD-' . $kind . '-' . (string) Str::ulid();
    }
}
