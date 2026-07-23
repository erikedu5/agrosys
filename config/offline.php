<?php

return [
    'enabled' => (bool) env('POS_OFFLINE_ENABLED', true),
    'catalog_enabled' => (bool) env('POS_OFFLINE_CATALOG_ENABLED', true),
    'sales_enabled' => (bool) env('POS_OFFLINE_SALES_ENABLED', false),
    'sync_enabled' => (bool) env('POS_SYNC_PUSH_ENABLED', false),
    'valid_days' => (int) env('POS_OFFLINE_VALID_DAYS', 7),
    'require_activation' => (bool) env('POS_REQUIRE_ACTIVATION', false),
    'allow_negative_stock' => (bool) env('POS_OFFLINE_ALLOW_NEGATIVE_STOCK', true),
];
