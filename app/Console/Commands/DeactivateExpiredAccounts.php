<?php

namespace App\Console\Commands;

use App\Repositories\ConnectedAccountRepository;
use Illuminate\Console\Command;

/**
 * Command to deactivate expired connected accounts
 * Should be scheduled to run daily
 */
class DeactivateExpiredAccounts extends Command
{
    protected $signature = 'pipedream:deactivate-expired 
                            {--limit=100 : Maximum accounts to process per run}';

    protected $description = 'Deactivate expired connected accounts';

    public function __construct(
        private ConnectedAccountRepository $repository
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $this->info('Deactivating expired accounts...');

        $deactivated = $this->repository->deactivateExpiredConnections($limit);

        $this->info("Deactivated {$deactivated} expired accounts.");

        return Command::SUCCESS;
    }
}

