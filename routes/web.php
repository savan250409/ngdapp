<?php

use App\Http\Controllers\ApiDocController;
use App\Http\Controllers\AppController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Ngd001FrameCategoryController;
use App\Http\Controllers\Ngd001FrameController;
use App\Http\Controllers\Ngd001MilestoneFrameController;
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
Route::middleware(['auth', 'app.context'])->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // App management + switcher (super-admin). The gate is enforced in the controller.
    Route::post('/switch-app', [AppController::class, 'switch'])->name('apps.switch');
    Route::prefix('apps')->name('apps.')->group(function () {
        Route::get('/', [AppController::class, 'index'])->name('index');
        Route::post('/', [AppController::class, 'store'])->name('store');
        Route::delete('/{id}', [AppController::class, 'destroy'])->name('destroy');
    });

    // Frame Categories
    Route::prefix('frame-categories')->name('frame-categories.')->group(function () {
        Route::get('/', [Ngd001FrameCategoryController::class, 'index'])->name('index');
        Route::post('/order', [Ngd001FrameCategoryController::class, 'updateOrder'])->name('order');
        Route::post('/', [Ngd001FrameCategoryController::class, 'store'])->name('store');
        Route::put('/{id}', [Ngd001FrameCategoryController::class, 'update'])->name('update');
        Route::delete('/{id}', [Ngd001FrameCategoryController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/toggle-status', [Ngd001FrameCategoryController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Frames (the "frame builder")
    Route::prefix('frames')->name('frames.')->group(function () {
        Route::get('/', [Ngd001FrameController::class, 'index'])->name('index');
        Route::get('/create', [Ngd001FrameController::class, 'create'])->name('create');
        Route::post('/', [Ngd001FrameController::class, 'store'])->name('store');
        Route::post('/order', [Ngd001FrameController::class, 'updateOrder'])->name('order');
        Route::get('/category/{id}/frames', [Ngd001FrameController::class, 'categoryFrames'])->name('category-frames');
        Route::get('/{id}/edit', [Ngd001FrameController::class, 'edit'])->name('edit');
        Route::put('/{id}', [Ngd001FrameController::class, 'update'])->name('update');
        Route::delete('/{id}', [Ngd001FrameController::class, 'destroy'])->name('destroy');
    });

    // Milestone Frames (gender + month)
    Route::prefix('milestone-frames')->name('milestone-frames.')->group(function () {
        Route::get('/', [Ngd001MilestoneFrameController::class, 'index'])->name('index');
        Route::get('/create', [Ngd001MilestoneFrameController::class, 'create'])->name('create');
        Route::post('/', [Ngd001MilestoneFrameController::class, 'store'])->name('store');
        Route::post('/order', [Ngd001MilestoneFrameController::class, 'updateOrder'])->name('order');
        Route::get('/{id}/frames', [Ngd001MilestoneFrameController::class, 'recordFrames'])->name('frames');
        Route::get('/{id}/edit', [Ngd001MilestoneFrameController::class, 'edit'])->name('edit');
        Route::put('/{id}', [Ngd001MilestoneFrameController::class, 'update'])->name('update');
        Route::delete('/{id}', [Ngd001MilestoneFrameController::class, 'destroy'])->name('destroy');
    });

    // API documentation
    Route::get('/api-url', [ApiDocController::class, 'index'])->name('api-docs');
});
