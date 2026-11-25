<?php

namespace App\Providers;

use App\Models\Agenda;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($appUrl = config('app.url')) {
            URL::forceRootUrl($appUrl);

            if (str_starts_with($appUrl, 'https://')) {
                URL::forceScheme('https');
            }
        }

        Paginator::defaultView('vendor.pagination.korlas');

        View::composer('layouts.app', function ($view): void {
            $user = auth()->user();

            if (! $user) {
                return;
            }

            $view->with(
                'notificationCount',
                Agenda::query()
                    ->visibleTo($user)
                    ->where('tanggal', '>=', today()->toDateString())
                    ->count()
            );
        });
    }
}
