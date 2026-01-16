<?php

namespace ValueAdding\WebhookReceiver\Console\Commands;

use Illuminate\Console\Command;
use ValueAdding\WebhookReceiver\Models\WebhookSource;

class RegenerateTokenCommand extends Command
{
    protected $signature = 'webhook:regenerate {source_id : The ID of the source to regenerate token for}';

    protected $description = 'Regenerate the bearer token for a webhook source';

    public function handle(): int
    {
        $sourceId = $this->argument('source_id');
        $source = WebhookSource::find($sourceId);

        if (!$source) {
            $this->error("Webhook source with ID {$sourceId} not found.");
            return Command::FAILURE;
        }

        if (!$this->confirm("Are you sure you want to regenerate the token for '{$source->name}'? The old token will stop working immediately.")) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        $newToken = $source->regenerateToken();

        $this->info('Token regenerated successfully!');
        $this->newLine();
        $this->warn('New Bearer Token (save this, it will not be shown again):');
        $this->line($newToken);
        $this->newLine();
        $this->info('Update your sender application .env file:');
        $this->line("WEBHOOK_LOGGER_TOKEN={$newToken}");

        return Command::SUCCESS;
    }
}
