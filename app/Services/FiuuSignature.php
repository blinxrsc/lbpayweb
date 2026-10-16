<?php

namespace App\Services;

use App\Models\PaymentGatewaySetting;

/**
 * The two Fiuu (MOLPay) hashes in one place, so every controller verifies
 * callbacks the same way.
 */
class FiuuSignature
{
    /**
     * Outgoing request hash: vcode = md5(amount + merchant_id + order_id + verify_key)
     */
    public static function vcode(string $amountFormatted, PaymentGatewaySetting $gw, string $orderId): string
    {
        return md5($amountFormatted . $gw->merchant_id . $orderId . $gw->private_key);
    }

    /**
     * Incoming callback/return hash:
     *   key0 = md5(tranID + orderid + status + domain + amount + currency)
     *   skey = md5(paydate + domain + key0 + appcode + secret_key)
     *
     * hash_equals() is used instead of !== so the comparison takes constant
     * time and leaks nothing about how many characters matched.
     */
    public static function verify(array $in, PaymentGatewaySetting $gw): bool
    {
        $skey = (string) ($in['skey'] ?? '');
        if ($skey === '') {
            return false;
        }

        $key0 = md5(
            ($in['tranID'] ?? '') . ($in['orderid'] ?? '') . ($in['status'] ?? '') .
            ($in['domain'] ?? '') . ($in['amount'] ?? '') . ($in['currency'] ?? '')
        );
        $key1 = md5(
            ($in['paydate'] ?? '') . ($in['domain'] ?? '') . $key0 .
            ($in['appcode'] ?? '') . $gw->secret_key
        );

        return hash_equals($key1, $skey);
    }
}
