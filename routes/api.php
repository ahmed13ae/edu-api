<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\UserController;
use App\Models\Course;
use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
//auth api
Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);
Route::post('/logout', [UserController::class, 'logout'])->middleware('auth:sanctum');

//crud api
Route::get('/courses', [CourseController::class, 'index']);
Route::post('/courses', [CourseController::class, 'store']);
Route::put('/courses/{id}', [CourseController::class, 'update']);
Route::get('/courses/{id}', [CourseController::class, 'show']);
Route::delete('/courses/{id}', [CourseController::class, 'destroy']);
// Route::get('/{id}',function($id){
//     $provider=Provider::find($id);
//     $courses=$provider->courses;
    
//     return response()->json($courses,200);
// });