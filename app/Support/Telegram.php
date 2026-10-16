<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * One place to send ops alerts to the admin Telegram chat.
 * Keeps messages under Telegram's 4,096-character limit and never throws
 * (an alert failing must not break the command that sent it).
 */
final class Telegram
{
    public static function send(string $text): bool
    {
        $token = config('services.telegram.bot_token');
        $chat  = config('services.telegram.admin_chat_id');
        if (!$token || !$chat) {
            return false;
        }

        if (mb_strlen($text) > 4000) {
            $text = mb_substr($text, 0, 3990) . "\n…";
        }

        try {
            $res = Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id'    => $chat,
                'text'       => $text,
                'parse_mode' => 'Markdown',
            ]);
            if (!$res->successful()) {
                Log::error('Telegram alert failed: ' . $res->body());
            }
            return $res->successful();
        } catch (Throwable $e) {
            Log::error('Telegram alert failed: ' . $e->getMessage());
            return false;
        }
    }
}
