<?php

return [
    'enabled' => (bool) env('CAS_ENABLED', false),
    'server_url' => rtrim((string) env('CAS_SERVER_URL', ''), '/'),
    'service_url' => env('CAS_SERVICE_URL'),
    'timeout' => (int) env('CAS_TIMEOUT', 10),
];
