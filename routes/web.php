<?php

use App\Http\Controllers\Auth\Profile\ProfileController;
use App\Http\Controllers\Company\CompanySelectionController;
use App\Http\Controllers\Company\InvitationClaimController;
use App\Http\Controllers\Home\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('marketing.index');
})->name('marketing');

require __DIR__.'/auth.php';

Route::get('invitations/entry', [InvitationClaimController::class, 'entry'])->middleware('throttle:invitation-entry')->name('invitations.entry');
Route::get('invitations/review', [InvitationClaimController::class, 'review'])->name('invitations.review');
Route::post('invitations/accept', [InvitationClaimController::class, 'accept'])->middleware(['auth', 'auth.session', 'verified', 'throttle:invitation-accept'])->name('invitations.accept');

Route::middleware(['auth', 'auth.session', 'verified'])->group(function (): void {
    Route::get('companies/select', CompanySelectionController::class)->name('companies.select');
    Route::get('companies/{company}/home', [HomeController::class, 'home'])->name('home');

    Route::get('profile', [ProfileController::class, 'edit'])
        ->middleware('password.confirm')
        ->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])
        ->middleware('password.confirm')
        ->name('profile.update');
    Route::delete('profile', [ProfileController::class, 'destroy'])
        ->middleware('password.confirm')
        ->name('profile.destroy');

    require __DIR__.'/company.php';
    require __DIR__.'/customer.php';
    require __DIR__.'/supplier.php';
    require __DIR__.'/product.php';
});
