<?php

namespace ValueAdding\WebhookReceiver\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $configUsername = config('webhook-receiver.viewer.username');
        $configPassword = config('webhook-receiver.viewer.password');

        if (empty($configPassword)) {
            return response()->json(['error' => 'Viewer password not configured'], 500);
        }

        if ($request->input('username') === $configUsername && $request->input('password') === $configPassword) {
            session(['webhook_viewer_authenticated' => true]);

            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'Invalid credentials'], 401);
    }

    public function logout(Request $request): JsonResponse
    {
        session()->forget('webhook_viewer_authenticated');

        return response()->json(['success' => true]);
    }

    public function check(): JsonResponse
    {
        return response()->json([
            'authenticated' => session('webhook_viewer_authenticated', false),
        ]);
    }
}
