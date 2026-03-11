<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DeviceOutlet;
use Illuminate\Support\Facades\Http;

class MachineWatchdog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:machine-watchdog';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Alert if machines are offline for more than 15 minutes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Find machines that should be online but haven't checked in
        $offlineMachines = DeviceOutlet::with('outlet') // Eager load the outlet name
            ->where('status', 'online')
            ->where('last_seen_at', '<', now()->subMinutes(15))
            ->get();

        if ($offlineMachines->isEmpty()) {
            return;
        }
   
        \Log::info("Watchdog found " . $offlineMachines->count() . " machines to alert.");

        // 2. Group them by outlet
        $grouped = $offlineMachines->groupBy('outlet_id');

        foreach ($grouped as $outletId => $machines) {
            $outletName = $machines->first()->outlet->name ?? "Unknown Outlet (ID: $outletId)";
            $count = $machines->count();

            // 3. Mark all as offline in one query (efficient)
            DeviceOutlet::whereIn('id', $machines->pluck('id'))->update(['status' => 'offline']);

            // 4. Create a single summary message
            $message = "🚨 *OUTLET ALERT* 🚨\n\n"
                     . "📍 *Location:* {$outletName}\n"
                     . "⚠️ *Status:* {$count} machine(s) went OFFLINE.\n\n"
                     . "📝 *Machine IDs:*\n";
            
            foreach ($machines as $m) {
                $message .= "• `{$m->device_serial_number}`\n";
            }

            $message .= "\n⏰ *Detected at:* " . now()->format('H:i:s');

            // 5. Send to Telegram
            $this->sendTelegram($message);
        }
    }

    private function sendTelegram($text)
    {
        $response = Http::post("https://api.telegram.org/bot" . env('TELEGRAM_BOT_TOKEN') . "/sendMessage", [
            'chat_id' => env('TELEGRAM_CHAT_ID'),
            'text' => $text,
            'parse_mode' => 'Markdown'
        ]);

        if (!$response->successful()) {
            Log::error("Telegram Alert Failed: " . $response->body());
        }
    }
}
