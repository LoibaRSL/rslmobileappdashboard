<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TinRegistrationController;
use App\Http\Controllers\BusinessRegistrationController;
use App\Http\Controllers\BusinessAmendmentController;
use App\Http\Controllers\TinRegistrationsController;
use App\Http\Controllers\API\TINAmendmentController;

Route::middleware('api')->group(function () {
Route::post('/register-tin', [TinRegistrationController::class, 'register']);
Route::post('/business-registration', [BusinessRegistrationController::class, 'store']);
Route::get('/business-registrations', [BusinessRegistrationController::class, 'index']);
Route::get('/business-registration/{id}', [BusinessRegistrationController::class, 'show']);
Route::post('/amend', [BusinessAmendmentController::class, 'store']);

Route::prefix('tin-registration')->group(function () {
    Route::post('/register', [TinRegistrationsController::class, 'register']);
    Route::post('/verify-email', [TinRegistrationsController::class, 'verifyEmail']);
    Route::get('/status/{ref}', [TinRegistrationsController::class, 'checkStatus']);
    Route::get('/registration/{id}', [TinRegistrationsController::class, 'getRegistration']);
         // Amendment routes
    Route::get('/user-data/{tin}', [TinRegistrationsController::class, 'getUserDataByTin']);
    Route::post('/amend', [TinRegistrationsController::class, 'amend']);
    Route::get('/check-amendment/{tin}', [TinRegistrationsController::class, 'checkPendingAmendment']);

});

// Admin routes (protected)
Route::prefix('admin/tin-registrations')->group(function () {
    Route::get('/', [TinRegistrationsController::class, 'index']);
    Route::put('/{id}/status', [TinRegistrationsController::class, 'updateStatus']);
    Route::get('/{id}', [TinRegistrationsController::class, 'show']);
})->middleware(['auth:sanctum']);





});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/my-tin', [TINAmendmentController::class, 'getMyTIN']);
    Route::post('/amend-tin', [TINAmendmentController::class, 'updateTIN']);
});


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
