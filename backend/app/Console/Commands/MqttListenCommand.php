<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use App\Http\Controllers\Api\V1\IoTController;

class MqttListenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mqtt:listen';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listen MQTT messages from EMQX Broker';

    /**
     * Execute the console command.
     */
public function handle()
    {
    $this->info('====================================');
    $this->info(' MQTT Listener');
    $this->info('====================================');

    try {

        $client = new MqttClient(
            config('mqtt.host'),
            config('mqtt.port'),
            config('mqtt.client_id')
        );

        $settings = (new ConnectionSettings)
            ->setUsername(config('mqtt.username'))
            ->setPassword(config('mqtt.password'))
            ->setUseTls(config('mqtt.use_tls'));

        $this->line('Connecting to EMQX...');

        $client->connect($settings);

        $this->info('Connected to EMQX successfully.');

        $this->info('Subscribing to topic parking/+/status ...');


$client->subscribe(
    'parking/+/status',
    function (string $topic, string $message) {

        $data = json_decode($message, true);

        if (!is_array($data)) {
            $this->error('Invalid JSON received');
            return;
        }

        if (!isset($data['event'])) {
            $this->warn('Message ignored (no event)');
            return;
        }

        if ($data['event'] !== 'vehicle_detected') {
            $this->line('Release event received. Skipped.');
            return;
        }

        $required = [
    'device_id',
    'sensor_code',
    'location_id',
    'left_distance',
    'right_distance',
    'event',
];

foreach ($required as $field) {
    if (!isset($data[$field])) {
        $this->warn("Missing field: {$field}");
        return;
    }
}

        $this->info('');
        $this->info('=========== MQTT ===========');
        $this->line('Topic    : '.$topic);
        $this->line('Device   : '.$data['device_id']);
        $this->line('Sensor   : '.$data['sensor_code']);
        $this->line('Location : '.$data['location_id']);
        $this->line('Event    : '.$data['event']);
        $this->info('============================');


        $payload = [
            'sensor_code' => $data['sensor_code'],
            'location_id' => $data['location_id'],
            'entry_time' => now(),
            'vehicle_count' => 1,
            'detection_confidence' => null,
            'raw_distance' => min(
                $data['left_distance'],
                $data['right_distance']
            ),
            'device_event_id' => $data['device_id'].'-'.$data['read_time'],
        ];

        try {

            $controller = app(IoTController::class);

            $entry = $controller->saveVehicleDetection($payload);

            $this->info('Vehicle entry saved. ID: '.$entry->id);

        } catch (\Throwable $e) {

            $this->error($e->getMessage());

        }
    },
    0
);

        $this->info('Listening...');
        $this->info('Press CTRL+C to stop.');

$client->loop(true);

    } catch (\Throwable $e) {

        $this->error('MQTT Connection Failed');
        $this->error($e->getMessage());

        return self::FAILURE;
    }

    return self::SUCCESS;
    }
}
