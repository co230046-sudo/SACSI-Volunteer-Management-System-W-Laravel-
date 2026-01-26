<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Auth\AuthenticationException;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     */
    protected $levels = [
        //
    ];

    /**
     * A list of exception types that are not reported.
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of inputs that are never flashed for validation exceptions.
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e)
    {
        // ✅ Handle expired session (419 CSRF token mismatch)
        if ($e instanceof TokenMismatchException) {

            Auth::guard('admin')->logout();

            return redirect()
                ->route('login')
                ->with('success', 'Session expired. Please login again.');
        }

        return parent::render($request, $e);
    }
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return redirect()
            ->route('login')
            ->with('success', 'Session expired. Please login again.');
    }

}
