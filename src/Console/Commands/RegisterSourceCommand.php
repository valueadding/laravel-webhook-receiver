<?php

namespace ValueAdding\WebhookReceiver\Console\Commands;

use Illuminate\Console\Command;
use ValueAdding\WebhookReceiver\Models\WebhookSource;

class RegisterSourceCommand extends Command
{
    protected $signature = 'webhook:register {name : The name of the source application} {app_url : The URL of the source application}';

    protected $description = 'Register a new webhook source and generate a bearer token';

    public function handle(): int
    {
        $name = $this->argument('name');
        $appUrl = $this->argument('app_url');
        $token = WebhookSource::generateToken();

        $source = WebhookSource::create([
            'name' => $name,
            'app_url' => $appUrl,
            'bearer_token' => $token,
            'enabled' => true,
        ]);

        $this->info('Webhook source registered successfully!');
        $this->newLine();
        $this->table(
            ['ID', 'Name', 'App URL', 'Enabled'],
            [[$source->id, $source->name, $source->app_url, 'Yes']]
        );
        $this->newLine();
        $this->warn('Bearer Token (save this, it will not be shown again):');
        $this->line($token);
        $this->newLine();
        $this->info('Add this to your sender application .env file:');
        $this->line("WEBHOOK_LOGGER_TOKEN={$token}");

        return Command::SUCCESS;
    }
}
