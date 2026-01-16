<?php

namespace ValueAdding\WebhookReceiver\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use ValueAdding\WebhookReceiver\Models\WebhookSource;

class AuthenticateWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (empty($token)) {
            return response()->json(['error' => 'Unauthorized - No token provided'], 401);
        }

        $source = WebhookSource::findByToken($token);

        if (!$source) {
            return response()->json(['error' => 'Unauthorized - Invalid token'], 401);
        }

        // Attach the source to the request for use in controllers
        $request->attributes->set('webhook_source', $source);

        return $next($request);
    }
}
