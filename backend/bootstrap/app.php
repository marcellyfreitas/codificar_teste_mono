<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    // Aplicacao API-only: nao existe `routes/web.php`. As rotas web sao
    // omitidas de proposito, entao o grupo de middleware `web` (sessions,
    // CSRF) nunca e aplicado e nenhuma view e renderizada.
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Esta aplicacao e API-only: nao existe rota `login` (web).
        // Sem isso, o middleware `auth` tentaria redirecionar para route('login')
        // e estouraria RouteNotFoundException em toda rota protegida sem token.
        $middleware->redirectGuestsTo(null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
