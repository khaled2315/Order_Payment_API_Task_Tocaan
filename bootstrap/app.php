<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use App\Exceptions\UnauthorizedPaymentException;
use App\Exceptions\InvalidOrderStateException;
use App\Exceptions\InvalidPaymentAmountException;
use App\Exceptions\OrderNotFoundException;
use App\Exceptions\OrderHasPaymentsException;
use App\Exceptions\UnsupportedPaymentMethodException;

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
        // Render JSON responses for API routes
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Handle authorization exceptions
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'This action is unauthorized.',
                ], 403);
            }
        });

        // Handle access denied exceptions (from FormRequest authorization)
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'This action is unauthorized.',
                ], 403);
            }
        });

        // Handle not found exceptions
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Resource not found.',
                ], 404);
            }
        });

        // Handle custom order/payment exceptions
        $exceptions->render(function (UnauthorizedPaymentException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 403);
            }
        });

        $exceptions->render(function (OrderNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 404);
            }
        });

        $exceptions->render(function (InvalidOrderStateException|InvalidPaymentAmountException|OrderHasPaymentsException|UnsupportedPaymentMethodException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 422);
            }
        });
    })->create();
