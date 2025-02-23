<?php

use App\Http\Controllers\CourseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/courses',[CourseController::class,'index']);
Route::post('/courses',[CourseController::class,'store']);
Route::put('/courses/{id}',[CourseController::class,'update']);
Route::get('/courses/{id}',[CourseController::class,'show']);
Route::delete('/courses/{id}',[CourseController::class,'destroy']);
