<?php

namespace ValueAdding\WebhookReceiver\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WebhookSource extends Model
{
    protected $table = 'webhook_sources';

    protected $fillable = [
        'name',
        'app_url',
        'bearer_token',
        'enabled',
        'metadata',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'bearer_token',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(WebhookLog::class);
    }

    public static function generateToken(): string
    {
        return Str::random(64);
    }

    public function regenerateToken(): string
    {
        $this->bearer_token = self::generateToken();
        $this->save();

        return $this->bearer_token;
    }

    public static function findByToken(string $token): ?self
    {
        return self::where('bearer_token', $token)
            ->where('enabled', true)
            ->first();
    }
}
