<?php

namespace ValueAdding\WebhookReceiver\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use ValueAdding\WebhookReceiver\Models\WebhookLog;
use ValueAdding\WebhookReceiver\Models\WebhookSource;

class WebhookController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'level' => 'required|integer',
            'level_name' => 'required|string',
            'message' => 'required|string',
            'context' => 'nullable|array',
            'datetime' => 'required|string',
            'channel' => 'required|string',
            'extra' => 'nullable|array',
        ]);

        /** @var WebhookSource $source */
        $source = $request->attributes->get('webhook_source');

        $level = $request->input('level');
        $message = $request->input('message');
        $channel = $request->input('channel');
        $context = $request->input('context', []);
        $extra = $request->input('extra', []);

        // Generate hash for deduplication
        $hash = WebhookLog::generateHash($level, $message, $channel);

        // Check for deduplication
        $dedupEnabled = config('webhook-receiver.deduplication.enabled', true);
        $windowMinutes = config('webhook-receiver.deduplication.window_minutes', 5);

        if ($dedupEnabled) {
            $existingLog = WebhookLog::findDuplicate($source->id, $hash, $windowMinutes);

            if ($existingLog) {
                $existingLog->incrementOccurrence($context, $extra);

                return response()->json([
                    'success' => true,
                    'deduplicated' => true,
                    'occurrence_count' => $existingLog->occurrence_count,
                ]);
            }
        }

        // Create new log entry
        $now = Carbon::now();
        $log = WebhookLog::create([
            'webhook_source_id' => $source->id,
            'log_hash' => $hash,
            'level' => $level,
            'level_name' => $request->input('level_name'),
            'message' => $message,
            'context' => $context,
            'datetime' => Carbon::parse($request->input('datetime')),
            'channel' => $channel,
            'extra' => $extra,
            'occurrence_count' => 1,
            'first_seen_at' => $now,
            'last_seen_at' => $now,
        ]);

        return response()->json([
            'success' => true,
            'id' => $log->id,
        ], 201);
    }
}
