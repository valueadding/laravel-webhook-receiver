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

        // Mark as viewed when opened
        $log->markAsViewed();

        return response()->json($log);
    }

    public function destroy(int $id): JsonResponse
    {
        $log = WebhookLog::findOrFail($id);
        $log->delete();

        return response()->json(['success' => true]);
    }

    public function markViewed(int $id): JsonResponse
    {
        $log = WebhookLog::findOrFail($id);
        $log->markAsViewed();

        return response()->json(['success' => true, 'viewed_at' => $log->viewed_at]);
    }

    public function markAllViewed(Request $request): JsonResponse
    {
        $query = WebhookLog::unviewed();

        // Apply same filters as the list
        if ($request->filled('source_id')) {
            $query->bySource($request->input('source_id'));
        }
        if ($request->filled('level')) {
            $query->byLevel($request->input('level'));
        }
        if ($request->filled('channel')) {
            $query->byChannel($request->input('channel'));
        }
        if ($request->filled('search')) {
            $query->where('message', 'like', '%' . $request->input('search') . '%');
        }

        $count = $query->update(['viewed_at' => now()]);

        return response()->json(['success' => true, 'count' => $count]);
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

    public function stats(Request $request): JsonResponse
    {
        $query = WebhookLog::query();

        // Apply filters
        if ($request->filled('source_id')) {
            $query->bySource($request->input('source_id'));
        }
        if ($request->filled('level')) {
            $query->byLevel($request->input('level'));
        }
        if ($request->filled('channel')) {
            $query->byChannel($request->input('channel'));
        }
        if ($request->filled('search')) {
            $query->where('message', 'like', '%' . $request->input('search') . '%');
        }

        // Get filtered stats
        $stats = [
            'total_logs' => (clone $query)->count(),
            'total_sources' => (clone $query)->distinct('webhook_source_id')->count('webhook_source_id'),
            'logs_today' => (clone $query)->whereDate('last_seen_at', today())->count(),
            'errors_today' => (clone $query)->whereDate('last_seen_at', today())
                ->where('level', '>=', 400)
                ->count(),
            'unviewed' => (clone $query)->unviewed()->count(),
        ];

        // Get per-source breakdown
        $stats['by_source'] = WebhookSource::select('id', 'name')
            ->withCount(['logs as total' => function ($q) use ($request) {
                if ($request->filled('level')) {
                    $q->byLevel($request->input('level'));
                }
                if ($request->filled('channel')) {
                    $q->byChannel($request->input('channel'));
                }
                if ($request->filled('search')) {
                    $q->where('message', 'like', '%' . $request->input('search') . '%');
                }
            }])
            ->withCount(['logs as today' => function ($q) use ($request) {
                $q->whereDate('last_seen_at', today());
                if ($request->filled('level')) {
                    $q->byLevel($request->input('level'));
                }
                if ($request->filled('channel')) {
                    $q->byChannel($request->input('channel'));
                }
                if ($request->filled('search')) {
                    $q->where('message', 'like', '%' . $request->input('search') . '%');
                }
            }])
            ->withCount(['logs as errors_today' => function ($q) use ($request) {
                $q->whereDate('last_seen_at', today())->where('level', '>=', 400);
                if ($request->filled('level')) {
                    $q->byLevel($request->input('level'));
                }
                if ($request->filled('channel')) {
                    $q->byChannel($request->input('channel'));
                }
                if ($request->filled('search')) {
                    $q->where('message', 'like', '%' . $request->input('search') . '%');
                }
            }])
            ->withCount(['logs as unviewed' => function ($q) use ($request) {
                $q->unviewed();
                if ($request->filled('level')) {
                    $q->byLevel($request->input('level'));
                }
                if ($request->filled('channel')) {
                    $q->byChannel($request->input('channel'));
                }
                if ($request->filled('search')) {
                    $q->where('message', 'like', '%' . $request->input('search') . '%');
                }
            }])
            ->having('total', '>', 0)
            ->orderBy('name')
            ->get();

        return response()->json($stats);
    }
}
