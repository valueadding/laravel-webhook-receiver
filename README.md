# Laravel Webhook Receiver

A Laravel package that receives webhook logs from multiple applications, stores them with deduplication, and provides a React-based viewer.

**License:** Proprietary - Value Adding. No distribution without permission.

## Requirements

- PHP >= 8.1
- Laravel >= 10.0

## Installation

Add the repository to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:valueadding/laravel-webhook-receiver.git"
        }
    ]
}
```

Install via Composer:

```bash
composer require valueadding/laravel-webhook-receiver
```

Publish and run migrations:

```bash
php artisan migrate
```

Publish configuration (optional):

```bash
php artisan vendor:publish --provider="ValueAdding\WebhookReceiver\WebhookReceiverServiceProvider" --tag="config"
```

## Configuration

Add to your `.env` file:

```env
# Viewer authentication
WEBHOOK_VIEWER_USERNAME=admin
WEBHOOK_VIEWER_PASSWORD=your-secure-password

# Optional settings
WEBHOOK_RECEIVER_PREFIX=webhook
WEBHOOK_RECEIVER_DEDUP=true
WEBHOOK_RECEIVER_DEDUP_WINDOW=5
WEBHOOK_RECEIVER_RETENTION_DAYS=7
```

## Usage

### Register a Source Application

```bash
php artisan webhook:register "My App" "https://myapp.example.com"
```

This outputs a bearer token. Add it to the sender application's `.env`:

```env
WEBHOOK_LOGGER_URL=https://receiver.example.com/api/webhook/logs
WEBHOOK_LOGGER_TOKEN=<generated-token>
```

### Manage Sources

```bash
# List all sources
php artisan webhook:sources

# Regenerate token for a source
php artisan webhook:regenerate 1
```

### View Logs

Access the web viewer at: `https://your-app.com/webhook`

Login with the credentials from your `.env` file.

### Cleanup Old Logs

Logs are automatically cleaned up hourly via the scheduler. Manual cleanup:

```bash
php artisan webhook:cleanup
php artisan webhook:cleanup --days=14
```

Add to your scheduler in `app/Console/Kernel.php`:

```php
$schedule->command('webhook:cleanup')->hourly();
```

## API Endpoints

### Webhook Endpoint (Bearer Token Auth)

```
POST /api/webhook/logs
Authorization: Bearer <token>
X-App-Name: My App
X-App-Url: https://myapp.example.com

{
    "level": 400,
    "level_name": "ERROR",
    "message": "Something went wrong",
    "context": {},
    "datetime": "2024-01-16T12:00:00+00:00",
    "channel": "production",
    "extra": {}
}
```

### Viewer API (Session Auth)

- `GET /api/webhook/viewer/logs` - List logs (paginated)
- `GET /api/webhook/viewer/logs/{id}` - Get log details
- `GET /api/webhook/viewer/sources` - List sources
- `GET /api/webhook/viewer/levels` - List log levels
- `GET /api/webhook/viewer/channels` - List channels
- `GET /api/webhook/viewer/stats` - Get statistics

## Features

- **Bearer Token Authentication**: Each source app has a unique token
- **Deduplication**: Repeated identical logs are grouped with occurrence count
- **Auto Retention**: Old logs are automatically deleted after configurable period
- **React SPA Viewer**: Browse, filter, and search logs
- **Multi-Source**: Receive logs from multiple applications

## Author

Mike Smit - m.smit@valueadding.nl - Value Adding
