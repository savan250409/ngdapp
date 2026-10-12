<?php

use App\Http\Controllers\Api\FrameApiController;
use App\Http\Controllers\Api\MilestoneFrameApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Frame API (Bearer-token protected)
|--------------------------------------------------------------------------
| Send: Authorization: Bearer <API_TOKEN>
*/

Route::middleware('auth.apitoken')->group(function () {
    Route::get('/v1/getFrameCategories', [FrameApiController::class, 'getFrameCategories']);
    Route::post('/v1/getFramesByCategoryId', [FrameApiController::class, 'getFramesByCategoryId']);
    Route::post('/v1/getMonthlyFrameByCategoryId', [MilestoneFrameApiController::class, 'getMonthlyFrameByCategoryId']);
});
