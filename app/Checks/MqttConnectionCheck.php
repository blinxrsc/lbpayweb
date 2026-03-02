<?php

namespace App\Checks;

use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;
use PhpMqtt\Client\Facades\MQTT;

class MqttConnectionCheck extends Check
{
    // This is the name that will show on your dashboard
    protected ?string $name = 'MQTT Broker Connection';

    public function run(): Result
    {
        $result = Result::make();

        try {
            // Attempt a quick handshake with EMQX
            $mqtt = MQTT::connection();
            
            if ($mqtt) {
                return $result->ok('Successfully connected to EMQX.');
            }
            
            return $result->failed('Could not establish MQTT connection.');
        } catch (\Exception $e) {
            return $result->failed("MQTT Connection Error: {$e->getMessage()}");
        }
    }
}