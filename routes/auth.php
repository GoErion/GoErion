<?php


use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

Route::get('login',[LoginController::class,'index'])
    ->name('login');
//Route::post('login',[LoginController::class,'store'])

Route::get('register',[RegisterController::class,'index'])
    ->name('register');
Route::post('register',[RegisterController::class,'store'])
    ->name('register.store');

