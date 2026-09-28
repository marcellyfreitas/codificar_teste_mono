<?php

namespace App\Providers;

use App\Models\Ticket;
use App\Policies\TicketPolicy;
use App\Services\AuthService;
use App\Services\TicketService;
use App\Services\UserService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TicketService::class, function ($app) {
            return new TicketService;
        });

        $this->app->singleton(AuthService::class, function ($app) {
            return new AuthService;
        });

        $this->app->singleton(UserService::class, function ($app) {
            return new UserService;
        });
    }

    public function boot(): void
    {
        Gate::policy(Ticket::class, TicketPolicy::class);

        Gate::define('balance', [TicketPolicy::class, 'balance']);
        Gate::define('unassign-open', [TicketPolicy::class, 'unassignOpen']);
    }
}
