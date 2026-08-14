<?php

use App\Http\Controllers\Auth\Profile\ProfileController;
use App\Http\Controllers\Company\CompanyController;
use App\Http\Controllers\Home\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/',[HomeController::class,'home'])
    ->name('home');
//include auth route
require __DIR__ . '/auth.php';
//profile
Route::get('/profile', [ProfileController::class, 'edit'])
    ->name('profile.edit');
Route::patch('/profile', [ProfileController::class, 'update'])
    ->name('profile.update');
Route::delete('/profile', [ProfileController::class, 'destroy'])
    ->name('profile.destroy');

    //company
Route::get('company',[CompanyController::class,'create'])
    ->name('company.create');
Route::post('company', [CompanyController::class, 'store'])
    ->name('company');