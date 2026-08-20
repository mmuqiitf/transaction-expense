<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ChartOfAccountController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Reports\ProfitLossController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware('auth')->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::resource('categories', CategoryController::class);
    Route::resource('chart-of-accounts', ChartOfAccountController::class);
    Route::resource('transactions', TransactionController::class);

    Route::get('reports/profit-loss', [ProfitLossController::class, 'index'])->name('reports.profit-loss.index');
    Route::get('reports/profit-loss/export', [ProfitLossController::class, 'export'])->name('reports.profit-loss.export');
});

require __DIR__.'/settings.php';
