<?php

namespace App\Console\Commands;

use App\Repositories\ConnectedAccountRepository;
use App\Services\PipedreamService;
use Illuminate\Console\Command;

/**
 * Command to sync connected accounts from Pipedream
 * Can be scheduled to run periodically for data consistency
 */
class SyncConnectedAccounts extends Command
{
    protected $signature = 'pipedream:sync-accounts 
                            {--hours=24 : Hours since last sync to consider}
                            {--limit=50 : Maximum accounts to sync per run}';

    protected $description = 'Sync connected account data from Pipedream API';

    public function __construct(
        private ConnectedAccountRepository $repository,
        private PipedreamService $service
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $limit = (int) $this->option('limit');

        $this->info("Syncing accounts that haven't been synced in {$hours} hours...");

        $accounts = $this->repository->getConnectionsNeedingSync($hours, $limit);
        
        if ($accounts->isEmpty()) {
            $this->info('No accounts need syncing.');
            return Command::SUCCESS;
        }

        $this->info("Found {$accounts->count()} accounts to sync.");

        $bar = $this->output->createProgressBar($accounts->count());
        $bar->start();

        $synced = 0;
        $failed = 0;

        foreach ($accounts as $account) {
            try {
                $details = $this->service->getAccountDetails($account->pipedream_account_id);
                
                if ($details) {
                    $account->update([
                        'metadata' => array_merge($account->metadata, $details),
                        'last_synced_at' => now(),
                    ]);
                    $account->markAsSynced();
                    $synced++;
                } else {
                    $account->markAsError('Failed to fetch account details');
                    $failed++;
                }
            } catch (\Exception $e) {
                $account->markAsError($e->getMessage());
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Sync complete: {$synced} synced, {$failed} failed.");

        return Command::SUCCESS;
    }
}

