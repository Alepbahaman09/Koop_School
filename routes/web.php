<?php

use App\Http\Controllers\CashierController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\HomeBannerController;
use App\Http\Controllers\ManageStockController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockPurchaseController;
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
    Route::get('financial-report', [FinancialReportController::class, 'index'])->name('financial-report');
    Route::get('financial-report/export', [FinancialReportController::class, 'export'])->name('financial-report.export');
    Route::redirect('finance', '/financial-report');
    Route::redirect('finance/export', '/financial-report/export');

    Route::resource('suppliers', SupplierController::class)->except(['create', 'edit', 'show']);
    Route::post('stock-purchases/product-inline', [StockPurchaseController::class, 'storeProductInline'])->name('stock-purchases.product-inline');
    Route::post('stock-purchases/{stock_purchase}/receive', [StockPurchaseController::class, 'markAsReceived'])->name('stock-purchases.receive');
    Route::resource('stock-purchases', StockPurchaseController::class)->only(['index', 'create', 'store', 'show']);

    // Manage Stock (manual adjustments)
    Route::get('manage-stock', [ManageStockController::class, 'index'])->name('manage-stock.index');
    Route::post('manage-stock', [ManageStockController::class, 'store'])->name('manage-stock.store');

    // ── Cashier Terminal (dedicated — separate from app PaymentController) ──
    Route::post('cashier/sale', [CashierController::class, 'sale'])->name('cashier.sale');
    Route::get('cashier/sale', function () {
        return redirect()->route('payment.index');
    });
    Route::post('cashier/card-lookup', [CashierController::class, 'cardLookup'])->name('cashier.card-lookup');
    Route::get('cashier/history', [CashierController::class, 'history'])->name('cashier.history');
    Route::get('cashier/order/{orderNumber}', [CashierController::class, 'orderDetail'])->name('cashier.order-detail');

    Route::view('settings', 'settings')->name('settings');

    Route::get('notifications/changes', [NotificationController::class, 'changes'])->name('notifications.changes');
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
});

Route::redirect('analytics', '/financial-report')->middleware(['auth', 'verified', 'admin'])->name('analytics');
Route::redirect('analytics/export', '/financial-report/export')->middleware(['auth', 'verified', 'admin'])->name('analytics.export');

require __DIR__.'/auth.php';
