<?php

use App\Http\Controllers\Api\Ngd001FrameApiController;
use App\Http\Controllers\Api\Ngd001MilestoneFrameApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API (per-app, Bearer-token protected)
|--------------------------------------------------------------------------
| Base: /api/{app}/v1/...   e.g. /api/ngd001/v1/getFrameCategories
| Send: Authorization: Bearer <API_TOKEN>
*/

Route::prefix('{app}')->middleware(['app.route', 'auth.apitoken'])->group(function () {
    Route::get('/v1/getFrameCategories', [Ngd001FrameApiController::class, 'getFrameCategories']);
    Route::post('/v1/getFramesByCategoryId', [Ngd001FrameApiController::class, 'getFramesByCategoryId']);
    Route::post('/v1/getMonthlyFrameByCategoryId', [Ngd001MilestoneFrameApiController::class, 'getMonthlyFrameByCategoryId']);
});
