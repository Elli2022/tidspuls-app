<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(
                $request->user()?->getAuthIdentifier() ?: $request->ip()
            );
        });

        ResetPassword::createUrlUsing(function ($notifiable, string $token): string {
            $base = rtrim((string) config('app.frontend_url'), '/');

            if ($base === '') {
                $base = rtrim((string) config('app.url'), '/');

                if (config('app.env') === 'production') {
                    Log::warning('FRONTEND_URL is empty; password reset links fall back to APP_URL — set FRONTEND_URL to your Netlify SPA URL.');
                }
            }

            return $base.'/reset-password?token='.urlencode($token)
                .'&email='.urlencode($notifiable->getEmailForPasswordReset());
        });
    }
}
