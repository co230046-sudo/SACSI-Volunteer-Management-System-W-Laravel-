<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Auth;
use App\Services\FactLogger;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->render(function (
            \Illuminate\Auth\AuthenticationException $e,
            $request
        ) {

            Auth::guard('admin')->logout();

            $logger = app(FactLogger::class);

            $logger->log(
                type: 'auth.session_expired',
                action: 'expired',
                entity: 'AdminAccount',
                entityId: null,
                details: [
                    'summary' => 'Session expired (unauthenticated)',
                    'data' => [
                        'url' => $request->fullUrl(),
                    ],
                ]
            );

            return redirect()
                ->route('login')
                ->with('success', 'Session expired. Please login again.');
        });

        $exceptions->render(function (
            \Illuminate\Session\TokenMismatchException $e,
            $request
        ) {

            Auth::guard('admin')->logout();

            $logger = app(FactLogger::class);

            $logger->log(
                type: 'auth.session_expired',
                action: 'csrf_expired',
                entity: 'AdminAccount',
                entityId: null,
                details: [
                    'summary' => 'Session expired (CSRF token mismatch)',
                    'data' => [
                        'url' => $request->fullUrl(),
                    ],
                ]
            );

            return redirect()
                ->route('login')
                ->with('success', 'Session expired. Please login again.');
        });

    })
    ->create();
