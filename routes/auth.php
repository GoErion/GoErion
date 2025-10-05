<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;

//login routes
Route::get('login',[LoginController::class,'index'])
    ->name('login');
Route::post('login',[LoginController::class,'store'])
    ->name('login.store');
//register routes
Route::get('register',[RegisterController::class,'index'])
    ->name('register');
Route::post('register',[RegisterController::class,'store'])
    ->name('register.store');
//mnemonic
Route::get('mnemonic/show',[RegisterController::class,'showMnemonic'])
    ->name('mnemonic');
