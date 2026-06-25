<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
        $exceptions->renderable(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Validation failed.', 'errors' => $e->errors()], 422);
            }
        });
       $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return;
            }

            return match (true) {
                $e instanceof ModelNotFoundException       => response()->json(['message' => 'Resource not found.'], 404),
                $e instanceof NotFoundHttpException        => response()->json(['message' => 'Route not found.'], 404),
                $e instanceof MethodNotAllowedHttpException => response()->json(['message' => 'Method not allowed.'], 405),
                $e instanceof AuthenticationException      => response()->json(['message' => 'Unauthenticated.'], 401),
                $e instanceof AuthorizationException       => response()->json(['message' => 'Unauthorized.'], 403),
                $e instanceof ValidationException          => response()->json(['message' => 'Validation failed.', 'errors' => $e->errors()], 422),
                $e instanceof Nette\Schema\ValidationException => response()->json(['message' => 'Schema validation failed.', 'errors' => $e->errors()], 422),
                default                                    => response()->json(['message' => 'Server error.'], 500),
            };
        });
    })->create();
