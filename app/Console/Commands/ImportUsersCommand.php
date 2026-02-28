<?php

namespace App\Console\Commands;

use App\Services\RandomUserService;
use Illuminate\Console\Command;

class ImportUsersCommand extends Command
{
    protected $signature = 'users:import {--count=50 : Number of users to import}';
    protected $description = 'Import users from randomuser.me API into the database';

    public function handle(RandomUserService $service): int
    {
        $count = (int) $this->option('count');

        $this->info("Fetching {$count} users from randomuser.me API...");

        try {
            $result = $service->fetchAndImport($count);

            $this->newLine();
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total fetched', $result['total']],
                    ['Newly imported', $result['imported']],
                    ['Updated existing', $result['updated']],
                ]
            );

            $this->newLine();
            $this->info('✅ Import completed successfully!');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Import failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
