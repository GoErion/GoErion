<?php

use App\Http\Controllers\Home\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/',[HomeController::class,'home'])
    ->name('home');

//include auth route
require __DIR__ . '/auth.php';
