<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerCartController;
use App\Http\Controllers\CustomerLoginController;
use App\Http\Controllers\CustomerMenuController;
use App\Http\Controllers\CustomerMenuOrderController;
use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MejaController;
use App\Http\Controllers\MenuBrowseController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\OperationalHoursController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TableEntryController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/menu', [MenuBrowseController::class, 'index'])->name('menu.index');
Route::patch('/menu/{menuModel}/availability', [MenuBrowseController::class, 'toggleAvailability'])->name('menu.availability.toggle');
Route::get('/t/{code}', [TableEntryController::class, 'show'])->name('table.entry');
Route::get('/t/{code}/{serviceType}', [TableEntryController::class, 'select'])
    ->whereIn('serviceType', ['dine_in', 'takeaway'])
    ->name('table.entry.select');

Route::prefix('customer')->name('customer.')->group(function () {
    Route::get('/login', [CustomerLoginController::class, 'create'])->name('login');
    Route::post('/login', [CustomerLoginController::class, 'store'])->name('login.store');

    Route::middleware('customer')->group(function () {
        Route::post('/logout', [CustomerLoginController::class, 'destroy'])->name('logout');
        Route::get('/menu', [CustomerMenuController::class, 'index'])->name('menu.index');
        Route::get('/menu/{menuModel}', [CustomerMenuOrderController::class, 'show'])->name('menu.show');
        Route::post('/menu/{menuModel}', [CustomerMenuOrderController::class, 'store'])->name('menu.store');
        Route::get('/cart', [CustomerCartController::class, 'index'])->name('cart.index');
        Route::patch('/cart/items/{index}', [CustomerCartController::class, 'update'])->name('cart.items.update');
        Route::delete('/cart/items/{index}', [CustomerCartController::class, 'destroy'])->name('cart.items.destroy');
        Route::post('/cart/checkout', [CustomerCartController::class, 'checkout'])->name('cart.checkout');
        Route::get('/order', [CustomerOrderController::class, 'index'])->name('order.index');
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

    Route::get('/mejas', [MejaController::class, 'index'])->name('mejas.index');
    Route::get('/mejas/create', [MejaController::class, 'create'])->name('mejas.create');
    Route::post('/mejas', [MejaController::class, 'store'])->name('mejas.store');
    Route::get('/mejas/{meja}', [MejaController::class, 'show'])->name('mejas.show');
    Route::put('/mejas/{meja}', [MejaController::class, 'update'])->name('mejas.update');
    Route::delete('/mejas/{meja}', [MejaController::class, 'destroy'])->name('mejas.destroy');

    Route::get('/hours', [OperationalHoursController::class, 'edit'])->name('hours.edit');
    Route::put('/hours', [OperationalHoursController::class, 'update'])->name('hours.update');
    Route::post('/hours/closures', [OperationalHoursController::class, 'storeClosure'])->name('hours.closures.store');
    Route::delete('/hours/closures/{closure}', [OperationalHoursController::class, 'destroyClosure'])->name('hours.closures.destroy');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    Route::get('/reports/menus', [ReportController::class, 'menus'])->name('reports.menus');

    Route::get('/transaction', [TransactionController::class, 'index'])->name('transaction.index');
    Route::get('/transaction/history', [TransactionController::class, 'history'])->name('transaction.history');
    Route::get('/transaction/create', [TransactionController::class, 'create'])->name('transaction.create');
    Route::post('/transaction', [TransactionController::class, 'store'])->name('transaction.store');
    Route::get('/transaction/{transaction}', [TransactionController::class, 'show'])->name('transaction.show');
    Route::post('/transaction/{transaction}/items', [TransactionController::class, 'storeItem'])->name('transaction.items.store');
    Route::patch('/transaction/{transaction}/status', [TransactionController::class, 'updateStatus'])->name('transaction.status.update');
    Route::delete('/transaction/{transaction}', [TransactionController::class, 'destroy'])->name('transaction.destroy');
});
