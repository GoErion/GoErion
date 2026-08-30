<?php
declare(strict_types=1);

use App\Http\Controllers\Product\BrandController;
use App\Http\Controllers\Product\CategoryController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\Product\UnitController;
use Illuminate\Support\Facades\Route;


   //categories
Route::get('company/{company}/category/create',[CategoryController::class, 'create'])
    ->name('category.create');
Route::post('company/{company}/category',[CategoryController::class, 'store'])
    ->name('category.store');
//brands
Route::get('/company/{company}/brands',[BrandController::class, 'index'])
    ->name('brand.index');
Route::get('/company/{company}/brands/create',[BrandController::class, 'create'])
    ->name('brand.create');
Route::post('/company/{company}/brands',[BrandController::class, 'store'])
    ->name('brand.store');
//units
Route::get('/company/{company}/units',[UnitController::class, 'index'])
    ->name('unit.index');
Route::get('/company/{company}/units/create',[UnitController::class, 'create'])
    ->name('unit.create');
Route::post('/company/{company}/units',[UnitController::class, 'store'])
    ->name('unit.store');
//product
Route::get('/company/{company}/products',[ProductController::class, 'index'])
    ->name('product.index');
Route::get('/company/{company}/products/create',[ProductController::class, 'create'])
    ->name('product.create');
Route::post('/company/{company}/products',[ProductController::class, 'store'])
    ->name('product.store');
Route::get('company/{company}/products/{product}',[ProductController::class,'show'])        ->name('product.show');