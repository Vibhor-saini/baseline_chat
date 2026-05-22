<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Conversation;

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
