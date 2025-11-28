<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== FAILED JOBS ===\n";
$failed = DB::table('failed_jobs')
    ->orderBy('failed_at', 'desc')
    ->limit(5)
    ->get(['id', 'queue', 'failed_at']);

foreach ($failed as $job) {
    echo "Queue: {$job->queue} - Failed: {$job->failed_at}\n";
}

echo "\n=== PENDING JOBS ===\n";
$pending = DB::table('jobs')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get(['id', 'queue', 'created_at']);

foreach ($pending as $job) {
    echo "Queue: {$job->queue} - Created: {$job->created_at}\n";
}
