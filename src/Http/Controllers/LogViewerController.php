<?php

namespace ValueAdding\WebhookReceiver\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use ValueAdding\WebhookReceiver\Models\WebhookLog;
use ValueAdding\WebhookReceiver\Models\WebhookSource;

class LogViewerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = WebhookLog::with('source')
            ->orderBy('last_seen_at', 'desc');

        // Filter by source
        if ($request->filled('source_id')) {
            $query->bySource($request->input('source_id'));
        }

        // Filter by level
        if ($request->filled('level')) {
            $query->byLevel($request->input('level'));
        }

        // Filter by channel
        if ($request->filled('channel')) {
            $query->byChannel($request->input('channel'));
        }

        // Search in message
        if ($request->filled('search')) {
            $query->where('message', 'like', '%' . $request->input('search') . '%');
        }

        $perPage = $request->input('per_page', 25);
        $logs = $query->paginate($perPage);

        return response()->json($logs);
    }

    public function show(int $id): JsonResponse
    {
        $log = WebhookLog::with('source')->findOrFail($id);

        return response()->json($log);
    }

    public function sources(): JsonResponse
    {
        $sources = WebhookSource::select('id', 'name', 'app_url', 'enabled')
            ->withCount('logs')
            ->orderBy('name')
            ->get();

        return response()->json($sources);
    }

    public function levels(): JsonResponse
    {
        $levels = WebhookLog::select('level_name')
            ->distinct()
            ->orderBy('level_name')
            ->pluck('level_name');

        return response()->json($levels);
    }

    public function channels(): JsonResponse
    {
        $channels = WebhookLog::select('channel')
            ->distinct()
            ->orderBy('channel')
            ->pluck('channel');

        return response()->json($channels);
    }

    public function stats(): JsonResponse
    {
        $stats = [
            'total_logs' => WebhookLog::count(),
            'total_sources' => WebhookSource::count(),
            'logs_today' => WebhookLog::whereDate('last_seen_at', today())->count(),
            'errors_today' => WebhookLog::whereDate('last_seen_at', today())
                ->where('level', '>=', 400)
                ->count(),
        ];

        return response()->json($stats);
    }
}
