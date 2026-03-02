<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpMqtt\Client\Facades\MQTT;

class TestMqtt extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-mqtt';

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
        $topic = 'test/connection';
        $message = json_encode([
            'status' => 'success',
            'message' => 'Hello from Laravel EC2!',
            'time' => now()->toDateTimeString()
        ]);

        $this->info("Sending message to EMQX...");
        
        // This sends the message
        MQTT::publish($topic, $message);

        $this->info("Done! Check your EMQX Dashboard.");
    }
}
