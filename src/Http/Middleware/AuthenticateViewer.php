<?php

namespace ValueAdding\WebhookReceiver\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateViewer
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session('webhook_viewer_authenticated')) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            return redirect()->route('webhook.login');
        }

        return $next($request);
    }
}
