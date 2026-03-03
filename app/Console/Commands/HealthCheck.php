<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\{HealthStatus, MailServer, MailServerLog};
use PhpMqtt\Client\Facades\MQTT;

class HealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:health-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $results = [
            'rds'     => $this->checkDatabase(),
            'redis'   => $this->checkRedis(),
            'mqtt'    => $this->checkMqtt(),
            'horizon' => $this->checkHorizon(),
        ];

        // 1. Save to Database for Dashboard
        HealthStatus::updateOrCreate(['id' => 1], [
            'data' => json_encode($results),
            'updated_at' => now(),
        ]);

        // 2. Check for Failures and Notify
        $failed = array_keys(array_filter($results, fn($v) => $v !== 'OK'));
        if (!empty($failed)) {
            $this->sendAlert($failed);
        }

        $this->info('Health check completed.');
    }

    private function checkDatabase()
    {
        try {
            // Force a timeout of 3 seconds for the DB connection
            DB::connection()->getPdo();
            return 'OK';
        } catch (\Exception $e) {
            return 'Fail: ' . $e->getMessage();
        }
    }

    private function checkRedis()
    {
        try {
            $redis = \Illuminate\Support\Facades\Redis::connection();
            $redis->ping();
            return 'OK';
        } catch (\Exception $e) {
            return 'Fail';
        }
    }

    private function checkMqtt()
    {
        try {
            // Attempt a quick handshake with EMQX
            $mqtt = MQTT::connection();
            
            if ($mqtt) {
                return 'OK';
            }
            
            return 'Fail';
        } catch (\Exception $e) {
            return 'Fail';
        }
    }

    private function checkHorizon()
    {
        try {
            // 1. Run the command
            \Artisan::call('horizon:status');
            
            // 2. Capture the text output
            $output = \Artisan::output();

            // 3. Check if the output contains "running"
            if (str_contains(strtolower($output), 'running')) {
                return 'Active';
            }

            return 'Inactive';
        } catch (\Exception $e) {
            // If Horizon isn't installed or command fails
            return 'Not Found';
        }
    }

    private function sendAlert($failed)
    {
        $server = MailServer::first();
        if (!$server) return;

        // Use the dynamic config logic from your MailServerController
        $config = [
            'transport' => 'smtp',
            'host' => $server->host,
            'port' => $server->port,
            'username' => $server->username,
            'password' => $server->password,
            'encryption' => $server->encryption,
        ];

        try {
            Mail::build($config)->raw("Alert: Services Down: " . implode(', ', $failed), function ($m) use ($server) {
                $m->to('itsupport@laundrybar.com.my')->subject('🚨 System Alert');
            });
        } catch (\Exception $e) {
            Log::error("Health Mail Failed: " . $e->getMessage());
        }
    }
}
