<?php

namespace ValueAdding\WebhookReceiver\Console\Commands;

use Illuminate\Console\Command;
use ValueAdding\WebhookReceiver\Models\WebhookSource;

class ListSourcesCommand extends Command
{
    protected $signature = 'webhook:sources';

    protected $description = 'List all registered webhook sources';

    public function handle(): int
    {
        $sources = WebhookSource::withCount('logs')->get();

        if ($sources->isEmpty()) {
            $this->warn('No webhook sources registered yet.');
            $this->info('Use "php artisan webhook:register {name} {app_url}" to register a source.');
            return Command::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'App URL', 'Enabled', 'Log Count', 'Created'],
            $sources->map(function ($source) {
                return [
                    $source->id,
                    $source->name,
                    $source->app_url,
                    $source->enabled ? 'Yes' : 'No',
                    $source->logs_count,
                    $source->created_at->format('Y-m-d H:i'),
                ];
            })
        );

        return Command::SUCCESS;
    }
}
