<?php

namespace ValueAdding\WebhookReceiver\Console\Commands;

use Illuminate\Console\Command;
use ValueAdding\WebhookReceiver\Models\WebhookLog;

class CleanupLogsCommand extends Command
{
    protected $signature = 'webhook:cleanup {--days= : Number of days to retain logs (overrides config)}';

    protected $description = 'Delete webhook logs older than the retention period';

    public function handle(): int
    {
        $days = $this->option('days') ?? config('webhook-receiver.retention_days', 7);

        $this->info("Cleaning up logs older than {$days} days...");

        $count = WebhookLog::olderThan($days)->count();

        if ($count === 0) {
            $this->info('No logs to clean up.');
            return Command::SUCCESS;
        }

        // Delete in batches for performance
        $deleted = 0;
        WebhookLog::olderThan($days)->chunkById(1000, function ($logs) use (&$deleted) {
            foreach ($logs as $log) {
                $log->delete();
                $deleted++;
            }
        });

        $this->info("Deleted {$deleted} log entries.");

        return Command::SUCCESS;
    }
}
