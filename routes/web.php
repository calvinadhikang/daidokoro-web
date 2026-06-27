<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerLoginController;
use App\Http\Controllers\CustomerMenuController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MenuBrowseController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\OperationalHoursController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/menu', [MenuBrowseController::class, 'index'])->name('menu.index');

Route::prefix('customer')->name('customer.')->group(function () {
    Route::get('/login', [CustomerLoginController::class, 'create'])->name('login');
    Route::post('/login', [CustomerLoginController::class, 'store'])->name('login.store');

    Route::middleware('customer')->group(function () {
        Route::get('/menu', [CustomerMenuController::class, 'index'])->name('menu.index');
    });
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/menus');
    Route::get('/menus', [MenuController::class, 'index'])->name('menus.index');
    Route::get('/menus/create', [MenuController::class, 'create'])->name('menus.create');
    Route::post('/menus', [MenuController::class, 'store'])->name('menus.store');
    Route::get('/menus/{menuModel}', [MenuController::class, 'show'])->name('menus.show');
    Route::put('/menus/{menuModel}', [MenuController::class, 'update'])->name('menus.update');

    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('categories.show');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    Route::get('/hours', [OperationalHoursController::class, 'edit'])->name('hours.edit');
    Route::put('/hours', [OperationalHoursController::class, 'update'])->name('hours.update');
    Route::post('/hours/closures', [OperationalHoursController::class, 'storeClosure'])->name('hours.closures.store');
    Route::delete('/hours/closures/{closure}', [OperationalHoursController::class, 'destroyClosure'])->name('hours.closures.destroy');

    Route::get('/transaction', [TransactionController::class, 'index'])->name('transaction.index');
    Route::get('/transaction/history', [TransactionController::class, 'history'])->name('transaction.history');
    Route::get('/transaction/create', [TransactionController::class, 'create'])->name('transaction.create');
    Route::post('/transaction', [TransactionController::class, 'store'])->name('transaction.store');
    Route::get('/transaction/{transaction}', [TransactionController::class, 'show'])->name('transaction.show');
    Route::post('/transaction/{transaction}/items', [TransactionController::class, 'storeItem'])->name('transaction.items.store');
    Route::patch('/transaction/{transaction}/status', [TransactionController::class, 'updateStatus'])->name('transaction.status.update');
    Route::delete('/transaction/{transaction}', [TransactionController::class, 'destroy'])->name('transaction.destroy');
});
