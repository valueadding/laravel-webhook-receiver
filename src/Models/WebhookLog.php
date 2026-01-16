<?php

namespace ValueAdding\WebhookReceiver\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class WebhookLog extends Model
{
    protected $table = 'webhook_logs';

    protected $fillable = [
        'webhook_source_id',
        'log_hash',
        'level',
        'level_name',
        'message',
        'context',
        'datetime',
        'channel',
        'extra',
        'occurrence_count',
        'first_seen_at',
        'last_seen_at',
    ];

    protected $casts = [
        'context' => 'array',
        'extra' => 'array',
        'datetime' => 'datetime',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'level' => 'integer',
        'occurrence_count' => 'integer',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(WebhookSource::class, 'webhook_source_id');
    }

    public static function generateHash(int $level, string $message, string $channel): string
    {
        return md5($level . '|' . $message . '|' . $channel);
    }

    public static function findDuplicate(int $sourceId, string $hash, int $windowMinutes = 5): ?self
    {
        return self::where('webhook_source_id', $sourceId)
            ->where('log_hash', $hash)
            ->where('last_seen_at', '>', Carbon::now()->subMinutes($windowMinutes))
            ->first();
    }

    public function incrementOccurrence(array $context = [], array $extra = []): void
    {
        $this->occurrence_count++;
        $this->last_seen_at = Carbon::now();
        $this->context = $context;
        $this->extra = $extra;
        $this->save();
    }

    public function scopeBySource($query, int $sourceId)
    {
        return $query->where('webhook_source_id', $sourceId);
    }

    public function scopeByLevel($query, string $levelName)
    {
        return $query->where('level_name', strtoupper($levelName));
    }

    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeOlderThan($query, int $days)
    {
        return $query->where('last_seen_at', '<', Carbon::now()->subDays($days));
    }
}
