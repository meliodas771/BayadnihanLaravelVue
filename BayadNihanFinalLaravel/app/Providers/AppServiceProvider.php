<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use App\Models\Notification;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure rate limiting for login attempts
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->input('email') . '|' . $request->ip());
        });

        // Share unread notification count with all views (for API, this might not be needed but keeping for consistency)
        // View::composer('*', function ($view) {
        //     if (auth()->check()) {
        //         $unreadCount = Notification::where('user_id', auth()->id())
        //             ->where('read', false)
        //             ->count();
        //         $view->with('unreadNotificationCount', $unreadCount);
        //     }
        // });

        $host = request()->getHost();

        // Exclude localhost/127.0.0.1 to allow local development
        $isLocalhost = in_array($host, ['localhost', '127.0.0.1', '::1']);

        // Detect if the host looks like a DevTunnel, Ngrok, or similar
        $isTunnel = str_contains($host, 'devtunnels.ms') || str_contains($host, 'ngrok-free.app');

        // Force HTTPS only when actually accessed via HTTPS (SSH tunnels, production)
        if (!$isLocalhost && !$isTunnel && (request()->isSecure() || config('app.env') === 'production')) {
            URL::forceScheme('https');
        }
    }
}
