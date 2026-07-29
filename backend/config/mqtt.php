<?php

return [

    'host' => env('MQTT_HOST'),

    'port' => (int) env('MQTT_PORT', 8883),

    'username' => env('MQTT_USERNAME'),

    'password' => env('MQTT_PASSWORD'),

    'client_id' => env('MQTT_CLIENT_ID', 'laravel-backend'),

    'use_tls' => filter_var(env('MQTT_USE_TLS', true), FILTER_VALIDATE_BOOL),

];