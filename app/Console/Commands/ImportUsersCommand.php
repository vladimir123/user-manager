<?php

namespace App\Console\Commands;

use App\Services\RandomUserService;
use Illuminate\Console\Command;

class ImportUsersCommand extends Command
{
    protected $signature = 'users:import
        {--count=50      : Number of users to import}
        {--api-version=  : API version to use: 1.4 (default) or 0.8}';

    protected $description = 'Import users from randomuser.me API into the database';

    public function handle(RandomUserService $service): int
    {
        $count   = (int) $this->option('count');
        $version = $this->option('api-version') ?: '1.4';

        return $this->runImport($service, $count, $version);
    }

    private function runImport(RandomUserService $service, int $count, string $version): int
    {
        $label = $version === '0.8' ? 'v0.8 (legacy)' : 'v1.4';
        $this->info("Fetching {$count} users from randomuser.me API ({$label})...");

        try {
            $result = $service->fetchAndImport($count, $version);

            $this->newLine();
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total fetched',    $result['total']],
                    ['Newly imported',   $result['imported']],
                    ['Updated existing', $result['updated']],
                ]
            );
            $this->newLine();
            $this->info('Import completed successfully!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Import failed: ' . $e->getMessage());

            // Offer legacy API fallback when v1.4 returns empty results
            if ($version === '1.4' && str_contains($e->getMessage(), 'returned no users')) {
                $this->newLine();
                $this->warn('The modern API (v1.4) returned no data.');

                if ($this->confirm('Would you like to try the legacy API (v0.8) instead?', true)) {
                    return $this->runImport($service, $count, '0.8');
                }
            }

            return Command::FAILURE;
        }
    }
}
