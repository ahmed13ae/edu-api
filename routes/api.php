<?php

use App\Http\Controllers\CityController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\FieldController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ReviewController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ***************Auth API Routes*******************
Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);
Route::post('/logout', [UserController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/forgot-password', [UserController::class, 'forgotPassword']);
Route::post('/reset-password', [UserController::class, 'resetPassword']);

//**************Protected Routes for Authenticated Users*****************
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});


Route::middleware('auth:sanctum')->group(function () {
    // Create review for course or provider
    Route::post('/courses/{id}/reviews', [ReviewController::class, 'storeCourseReview']);
    Route::post('/providers/{id}/reviews', [ReviewController::class, 'storeProviderReview']);

    // Update or delete user's own review (or admin)
    // put /reviews/1/?type=provider or /reviews/1/?type=course
    Route::put('/reviews/{id}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);
});


    


//***************************Admin-Only Routes (Require 'admin' role)*******************
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', function () {
        return response()->json(['message' => 'Welcome, Admin!']);
    });

    // Course Management - Only Admin Can Modify
    Route::post('/courses', [CourseController::class, 'store']);
    //note php cant handle form data files in put method to bypass this use post method in post man and pust _method as key and PUT as value in first  row of form data
    Route::put('/courses/{id}', [CourseController::class, 'update']);
    Route::delete('/courses/{id}', [CourseController::class, 'destroy']);
    //course provider routes
    Route::post('/providers',[ProviderController::class,'store']);
    //note php cant handle form data files in put method to bypass this use post method in post man and pust _method as key and PUT as value in first  row of form data
    Route::put('/providers/{id}',[ProviderController::class,'update']);
    Route::delete('/providers/{id}',[ProviderController::class,'destroy']);
});

//*********** * Courses - General access**********************
//courses routest
Route::get('/courses', [CourseController::class, 'index']);
Route::get('/courses/{id}', [CourseController::class, 'show']);
//provider routes
Route::get('/providers',[ProviderController::class,'index']);
Route::get('/providers/{id}',[ProviderController::class,'show']);
//helper routes
Route::get('/cities', [CityController::class, 'index']);
Route::get('/fields', [FieldController::class, 'index']);
// reviews routes
// Get /reviews/?type=provider  or Get /reviews/?type=course
Route::get('/reviews', [ReviewController::class, 'index']); // all
