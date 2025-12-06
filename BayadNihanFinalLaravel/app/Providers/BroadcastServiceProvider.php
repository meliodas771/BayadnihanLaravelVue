<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Temporarily set default guard to 'web' for broadcasting
        $originalGuard = config('auth.defaults.guard');
        config(['auth.defaults.guard' => 'web']);
        
        Broadcast::routes(['middleware' => ['web', 'auth:web']]);

        require base_path('routes/channels.php');
        
        // Restore original guard
        config(['auth.defaults.guard' => $originalGuard]);
    }
}

