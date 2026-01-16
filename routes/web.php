<?php

use Illuminate\Support\Facades\Route;

$prefix = config('webhook-receiver.route_prefix', 'webhook');

// SPA entry point - serves the React app
Route::get($prefix . '/{any?}', function () {
    return view('webhook-receiver::app');
})->where('any', '.*')->name('webhook.app');

// Redirect root to login/app
Route::get($prefix, function () {
    return redirect()->route('webhook.app');
})->name('webhook.login');
