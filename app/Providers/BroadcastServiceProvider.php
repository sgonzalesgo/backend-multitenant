<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

class BroadcastServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Log::info('BroadcastServiceProvider booted');

        Broadcast::routes([
            'middleware' => ['bearer_cookie', 'auth:api'],
        ]);

        require base_path('routes/channels.php');
    }
}
