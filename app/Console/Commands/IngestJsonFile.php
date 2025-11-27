<?php

namespace App\Console\Commands;

use App\Jobs\JsonFileIngestionJob;
use Illuminate\Console\Command;

class IngestJsonFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ingest:json {user_id} {file_path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ingest a stored JSON financial data file using AI mapping';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = (int) $this->argument('user_id');
        $filePath = $this->argument('file_path');

        $this->info("Dispatching ingestion job for User ID: {$userId}, File: {$filePath}");

        JsonFileIngestionJob::dispatch($userId, $filePath);

        $this->info('Job dispatched successfully.');
    }
}
