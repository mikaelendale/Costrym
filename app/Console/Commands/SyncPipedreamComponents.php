<?php

namespace App\Console\Commands;

use App\Services\PipedreamService;
use Illuminate\Console\Command;

class SyncPipedreamComponents extends Command
{
    protected $signature = 'pipedream:sync {app? : The app name to sync (e.g., notion, xero_accounting_api). Leave empty to sync all configured integrations.}';

    protected $description = 'Sync Pipedream components for configured integrations';

    public function handle()
    {
        $service = app(PipedreamService::class);
        $appName = $this->argument('app');

        if ($appName) {
            $this->info("Syncing components for: {$appName}");
            $result = $service->syncAppComponents($appName);

            if ($result['success']) {
                $this->info("✓ Successfully synced {$result['actions_count']} actions and {$result['triggers_count']} triggers");
            } else {
                $this->error("✗ Failed to sync: {$result['error']}");

                return 1;
            }
        } else {
            $this->info('Syncing all configured integrations...');
            $this->newLine();
            $result = $service->syncAllConfiguredIntegrations();

            if ($result['success']) {
                $this->info('✓ Successfully synced all integrations!');
                $this->newLine();
                $this->info("Total: {$result['total_actions']} actions, {$result['total_triggers']} triggers across {$result['total_integrations']} integrations");
                $this->newLine();

                if (! empty($result['synced'])) {
                    $this->table(
                        ['App ID', 'App Name', 'Display Name', 'Actions', 'Triggers'],
                        array_map(fn ($s) => [
                            $s['app_id'] ?? 'N/A',
                            $s['app_name'] ?? 'N/A',
                            $s['name'] ?? 'N/A',
                            $s['actions_count'] ?? 0,
                            $s['triggers_count'] ?? 0,
                        ], $result['synced'])
                    );
                }

                if (! empty($result['failed'])) {
                    $this->newLine();
                    $this->warn('⚠ Some integrations failed to sync:');
                    foreach ($result['failed'] as $failed) {
                        $this->line("  - {$failed['name']} ({$failed['app_name']}): {$failed['error']}");
                    }
                }
            } else {
                $this->error('✗ Some integrations failed to sync');
                $this->newLine();
                if (! empty($result['synced'])) {
                    $this->info('Successfully synced:');
                    $this->table(
                        ['App', 'Actions', 'Triggers'],
                        array_map(fn ($s) => [$s['name'], $s['actions_count'], $s['triggers_count']], $result['synced'])
                    );
                    $this->newLine();
                }
                if (! empty($result['failed'])) {
                    $this->error('Failed:');
                    foreach ($result['failed'] as $failed) {
                        $this->error("  - {$failed['name']} ({$failed['app_name']}): {$failed['error']}");
                    }
                }

                return 1;
            }
        }

        return 0;
    }
}
