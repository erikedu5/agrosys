<?php

return [
    'enabled' => (bool) env('POS_NATIVE_ENABLED', false),
    'token_days' => 30,
    'challenge_minutes' => 5,
    'offline_days' => (int) env('POS_OFFLINE_VALID_DAYS', 7),
    'snapshot_hours' => 24,
    'page_size' => 100,
    // Optional independent 32-byte signing seed, encoded as 64 hexadecimal characters.
    'signing_seed' => env('POS_LEASE_SIGNING_SEED'),
];
