<?php

use App\Http\Controllers\Api\FrameApiController;
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
});
