<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

use Illuminate\Support\Facades\Exceptions;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Auth\AuthenticationException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Share the logged-in admin with all views
        View::composer('*', function ($view) {
            $view->with('admin', Auth::guard('admin')->user());
        });

        // Session expired / CSRF mismatch (419)
        Exceptions::renderable(function (TokenMismatchException $e, $request) {

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Session expired. Please login again.',
                ], 419);
            }

            return redirect()
                ->route('auth.login')
                ->with('success', 'Session expired. Please login again.');
        });

        // Not authenticated (hits protected route via URL)
        Exceptions::renderable(function (AuthenticationException $e, $request) {

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Please login to continue.',
                ], 401);
            }

            return redirect()
                ->route('auth.login')
                ->with('success', 'Please login to continue.');
        });
    }
}
