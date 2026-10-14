<?php

// Separate process, own connection and HTTP kernel: exercises actual MySQL locks.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
while (microtime(true) < (float) getenv('POS_RACE_START')) {
    usleep(1000);
}
$request = Illuminate\Http\Request::create('/api/v1/pos/sync/push', 'POST', [], [], [], [
    'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json',
    'HTTP_AUTHORIZATION' => 'Bearer '.getenv('POS_RACE_TOKEN'),
], getenv('POS_RACE_BODY'));
$response = $kernel->handle($request);
echo json_encode(['httpStatus' => $response->getStatusCode(), 'body' => json_decode($response->getContent(), true)], JSON_THROW_ON_ERROR);
$kernel->terminate($request, $response);
