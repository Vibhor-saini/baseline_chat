<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\URL;
use App\Models\Conversation;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Force HTTPS in production
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        View::composer('*', function ($view) {
            if (auth()->check()) {
                $sentRequestsCount = Conversation::query()
                    ->where('status', 'pending')
                    ->where('user_one_id', auth()->id())
                    ->count();

                $pendingRequestsCount = Conversation::query()
                    ->where('status', 'pending')
                    ->where('user_two_id', auth()->id())
                    ->count();

                $view->with([
                    'sentRequestsCount' => $sentRequestsCount,
                    'pendingRequestsCount' => $pendingRequestsCount,
                ]);
            }
        });
    }
}