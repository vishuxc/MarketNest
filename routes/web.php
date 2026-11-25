<?php

use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\ListingBrowseController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\ListingOptionsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ListingController::class, 'home'])->name('home');
Route::get('/listings', [ListingBrowseController::class, 'index'])->name('listings.browse');
Route::get('/category/{category:slug}', [ListingBrowseController::class, 'category'])->name('listings.category');
Route::get('/city/{city:slug}', [ListingBrowseController::class, 'city'])->name('listings.city');
Route::get('/city/{city:slug}/{category:slug}', [ListingBrowseController::class, 'combined'])
    ->withoutScopedBindings()->name('listings.city-category');
Route::get('/listing/{listing:slug}', [ListingController::class, 'show'])->name('listings.show');

Route::prefix('listing-options')->name('listing-options.')->group(function () {
    Route::get('/categories/{category}/subcategories', [ListingOptionsController::class, 'subcategories'])
        ->whereNumber('category')->name('subcategories');
    Route::get('/countries/{country}/states', [ListingOptionsController::class, 'states'])
        ->whereNumber('country')->name('states');
    Route::get('/states/{state}/cities', [ListingOptionsController::class, 'cities'])
        ->whereNumber('state')->name('cities');
    Route::get('/cities/{city}/areas', [ListingOptionsController::class, 'areas'])
        ->whereNumber('city')->name('areas');
});

Route::middleware('guest')->group(function () {
    Route::view('/register', 'auth.register')->name('register');
    Route::post('/register', [RegistrationController::class, 'store'])->name('register.store');
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [SessionController::class, 'store'])->name('login.store');
});

Route::post('/logout', [SessionController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/sell', [ListingController::class, 'create'])->name('listings.create');
    Route::post('/listings', [ListingController::class, 'store'])->name('listings.store');
    Route::get('/my-listings', [ListingController::class, 'index'])->name('listings.index');
    Route::get('/my-listings/{listing}/edit', [ListingController::class, 'edit'])->whereNumber('listing')->name('listings.edit');
    Route::patch('/my-listings/{listing}', [ListingController::class, 'update'])->whereNumber('listing')->name('listings.update');
    Route::delete('/my-listings/{listing}', [ListingController::class, 'destroy'])->whereNumber('listing')->name('listings.destroy');
});
