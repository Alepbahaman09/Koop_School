<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\HomeBannerController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'admin'])
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::get('products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('products', [ProductController::class, 'store'])->name('products.store');
    Route::get('products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::patch('products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    Route::resource('categories', CategoryController::class)->except(['create', 'edit', 'show']);
    Route::post('home-banners/cleanup-expired', [HomeBannerController::class, 'cleanupExpired'])
        ->name('home-banners.cleanup-expired');
    Route::resource('home-banners', HomeBannerController::class)->except(['create', 'edit', 'show']);

    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/snapshot', [OrderController::class, 'snapshot'])->name('orders.snapshot');
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');
    Route::delete('orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');

    Route::get('users', [CustomerController::class, 'index'])->name('users.index');
    Route::get('users/{customer}', [CustomerController::class, 'show'])->name('users.show');

    Route::get('payments', [PaymentController::class, 'index'])->name('payment.index');
    Route::get('orders/{order}/pay', [PaymentController::class, 'checkout'])->name('orders.pay');
    Route::post('orders/{order}/pay/nfc', [PaymentController::class, 'processNfcPayment'])->name('orders.pay.nfc');
    Route::post('orders/{order}/pay/cash', [PaymentController::class, 'processCashPayment'])->name('orders.pay.cash');
    Route::get('finance', [FinanceController::class, 'index'])->name('finance');
    Route::get('finance/export', [FinanceController::class, 'export'])->name('finance.export');

    Route::resource('suppliers', SupplierController::class)->except(['create', 'edit', 'show']);

    Route::view('settings', 'settings')->name('settings');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
});

Route::get('analytics', [AnalyticsController::class, 'index'])
    ->middleware(['auth', 'verified', 'admin'])
    ->name('analytics');
Route::get('analytics/export', [AnalyticsController::class, 'export'])
    ->middleware(['auth', 'verified', 'admin'])
    ->name('analytics.export');

require __DIR__.'/auth.php';
