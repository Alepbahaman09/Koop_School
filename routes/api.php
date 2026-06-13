<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\MobileDocumentController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\UploadController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:api-auth');
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:api-auth');
    Route::post('auth/password-reset', [AuthController::class, 'passwordReset'])->middleware('throttle:api-auth');

    Route::get('auth/me', [AuthController::class, 'me'])
        ->middleware(['throttle:api-me', 'api.token']);
    Route::get('documents', [MobileDocumentController::class, 'query'])
        ->middleware(['throttle:api-documents', 'api.token']);
    Route::get('documents/{path}', [MobileDocumentController::class, 'show'])
        ->middleware(['throttle:api-documents', 'api.token'])
        ->where('path', '.*');

    Route::middleware(['throttle:api', 'api.token'])->group(function () {
        Route::get('products', [ProductController::class, 'index']);
        Route::get('products/{id}', [ProductController::class, 'show']);
        Route::get('categories', [ProductController::class, 'categories']);
        Route::delete('auth/me', [AuthController::class, 'destroy']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/reauthenticate', [AuthController::class, 'reauthenticate']);
        Route::post('auth/email/verification-notification', [AuthController::class, 'resendEmailVerification']);
        Route::post('auth/email/change-request', [AuthController::class, 'requestEmailChange']);
        Route::get('cards/{cardUid}/exists', [MobileDocumentController::class, 'cardExists']);
        Route::put('documents/{path}', [MobileDocumentController::class, 'upsert'])->where('path', '.*');
        Route::delete('documents/{path}', [MobileDocumentController::class, 'destroy'])->where('path', '.*');
        Route::post('batch', [MobileDocumentController::class, 'batch']);
        Route::post('uploads', [UploadController::class, 'store']);

        Route::middleware('admin')->group(function () {
            Route::get('customers', [CustomerController::class, 'index']);
            Route::post('customers', [CustomerController::class, 'store']);
            Route::get('orders', [OrderController::class, 'index']);
            Route::post('orders', [OrderController::class, 'store']);
            Route::get('orders/{id}', [OrderController::class, 'show']);
            Route::patch('orders/{id}/status', [OrderController::class, 'updateStatus']);
            Route::post('payments', [PaymentController::class, 'store']);
            Route::get('payments/{orderId}', [PaymentController::class, 'index']);
        });
    });
});
