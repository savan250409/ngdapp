<?php

use App\Http\Controllers\Api\FrameApiController;
use App\Http\Controllers\Api\MilestoneFrameApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API (per-app, Bearer-token protected)
|--------------------------------------------------------------------------
| Base: /api/{app}/v1/...   e.g. /api/ngd001/v1/getFrameCategories
| Send: Authorization: Bearer <API_TOKEN>
*/

Route::prefix('{app}')->middleware(['app.route', 'auth.apitoken'])->group(function () {
    Route::get('/v1/getFrameCategories', [FrameApiController::class, 'getFrameCategories']);
    Route::post('/v1/getFramesByCategoryId', [FrameApiController::class, 'getFramesByCategoryId']);
    Route::post('/v1/getMonthlyFrameByCategoryId', [MilestoneFrameApiController::class, 'getMonthlyFrameByCategoryId']);
});
