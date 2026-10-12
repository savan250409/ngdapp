<?php

use App\Http\Controllers\ApiDocController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FrameCategoryController;
use App\Http\Controllers\FrameController;
use App\Http\Controllers\MilestoneFrameController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest (login)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Admin panel (auth-protected)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Frame Categories
    Route::prefix('frame-categories')->name('frame-categories.')->group(function () {
        Route::get('/', [FrameCategoryController::class, 'index'])->name('index');
        Route::post('/order', [FrameCategoryController::class, 'updateOrder'])->name('order');
        Route::post('/', [FrameCategoryController::class, 'store'])->name('store');
        Route::put('/{id}', [FrameCategoryController::class, 'update'])->name('update');
        Route::delete('/{id}', [FrameCategoryController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/toggle-status', [FrameCategoryController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Frames (the "frame builder")
    Route::prefix('frames')->name('frames.')->group(function () {
        Route::get('/', [FrameController::class, 'index'])->name('index');
        Route::get('/create', [FrameController::class, 'create'])->name('create');
        Route::post('/', [FrameController::class, 'store'])->name('store');
        Route::post('/order', [FrameController::class, 'updateOrder'])->name('order');
        Route::get('/category/{id}/frames', [FrameController::class, 'categoryFrames'])->name('category-frames');
        Route::get('/{id}/edit', [FrameController::class, 'edit'])->name('edit');
        Route::put('/{id}', [FrameController::class, 'update'])->name('update');
        Route::delete('/{id}', [FrameController::class, 'destroy'])->name('destroy');
    });

    // Milestone Frames (gender + month)
    Route::prefix('milestone-frames')->name('milestone-frames.')->group(function () {
        Route::get('/', [MilestoneFrameController::class, 'index'])->name('index');
        Route::get('/create', [MilestoneFrameController::class, 'create'])->name('create');
        Route::post('/', [MilestoneFrameController::class, 'store'])->name('store');
        Route::post('/order', [MilestoneFrameController::class, 'updateOrder'])->name('order');
        Route::get('/{id}/frames', [MilestoneFrameController::class, 'recordFrames'])->name('frames');
        Route::get('/{id}/edit', [MilestoneFrameController::class, 'edit'])->name('edit');
        Route::put('/{id}', [MilestoneFrameController::class, 'update'])->name('update');
        Route::delete('/{id}', [MilestoneFrameController::class, 'destroy'])->name('destroy');
    });

    // API documentation
    Route::get('/api-url', [ApiDocController::class, 'index'])->name('api-docs');
});
