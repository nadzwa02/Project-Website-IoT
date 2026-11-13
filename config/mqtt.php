<?php

return [
    'host' => env('MQTT_HOST', '127.0.0.1'),
    'port' => (int) env('MQTT_PORT', 1883),
    'client_id' => env('MQTT_CLIENT_ID', 'jalas-backend-' . uniqid()),
    'username' => env('MQTT_USERNAME'),
    'password' => env('MQTT_PASSWORD'),
    'topic' => env('MQTT_TOPIC', 'jalas/sensor/data'),
    'status_topic' => env('MQTT_STATUS_TOPIC', 'jalas/status'),
    'qos' => (int) env('MQTT_QOS', 0),
    'use_tls' => (bool) env('MQTT_USE_TLS', false),
    'db_save_interval' => (int) env('MQTT_DB_SAVE_INTERVAL', 60),
    'alert_cooldown' => (int) env('MQTT_ALERT_COOLDOWN', 300),
];

