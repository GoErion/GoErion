<?php

use App\Http\Controllers\Auth\Profile\ProfileController;
use App\Http\Controllers\Company\BranchController;
use App\Http\Controllers\Company\CompanyController;
use App\Http\Controllers\Company\WarehouseController;
use App\Http\Controllers\Home\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/',[HomeController::class,'home'])
    ->name('home');
//profile
Route::get('/profile', [ProfileController::class, 'edit'])
    ->name('profile.edit');
Route::patch('/profile', [ProfileController::class, 'update'])
    ->name('profile.update');
Route::delete('/profile', [ProfileController::class, 'destroy'])
    ->name('profile.destroy');
                        //included files
//auth route
require __DIR__ . '/auth.php';
//company route
require __DIR__ . '/company.php';
//customer route
require __DIR__ . '/customer.php';
//supplier
require __DIR__ . '/supplier.php';
//product
require __DIR__ . '/product.php';