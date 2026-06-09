<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::get('products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('products', [ProductController::class, 'store'])->name('products.store');
    Route::get('products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::patch('products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    
    Route::resource('categories', CategoryController::class)->except(['create', 'edit', 'show']);
    
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');
    Route::delete('orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');
    
    Route::get('users', [CustomerController::class, 'index'])->name('users.index');
    Route::get('users/{customer}', [CustomerController::class, 'show'])->name('users.show');
    
    Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
});

Route::view('analytics', 'analytics')
    ->middleware(['auth', 'verified'])
    ->name('analytics');

Route::view('finance', 'finance')
    ->middleware(['auth', 'verified'])
    ->name('finance');

Route::view('settings', 'settings')
    ->middleware(['auth', 'verified'])
    ->name('settings');

Route::view('notifications', 'notifications')
    ->middleware(['auth', 'verified'])
    ->name('notifications');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
