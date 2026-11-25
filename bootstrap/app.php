<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

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
        $exceptions->render(function (PDOException $exception, Request $request) {
            if (! app()->isProduction()) {
                return null;
            }

            $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
            $driverCode = (int) ($exception->errorInfo[1] ?? 0);
            $connectionFailed = str_starts_with($sqlState, '08')
                || in_array($driverCode, [1044, 1045, 1049, 2002, 2003, 2006, 2013], true);

            if (! $connectionFailed) {
                return null;
            }

            $message = 'MarketNest is temporarily unavailable. Please try again later.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 503)
                : response($message, 503)->header('Content-Type', 'text/plain; charset=UTF-8');
        });
    })->create();
