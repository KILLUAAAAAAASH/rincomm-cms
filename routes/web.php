<?php

use App\Http\Controllers\Admin\HeroSlideController;
use App\Http\Controllers\Admin\ServiceApplicationController as AdminServiceApplicationController;
use App\Http\Controllers\Admin\SubscriberController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceApplicationController;
use App\Http\Controllers\ServiceCoverageController;
use App\Http\Controllers\Technician\DashboardController as TechnicianDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [LandingPageController::class, 'index'])
    ->name('home');

Route::get('/apply/coverage', [ServiceCoverageController::class, 'create'])
    ->name('apply.coverage');

Route::post('/apply/coverage', [ServiceCoverageController::class, 'check'])
    ->name('apply.coverage.check');


/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->name('login.store');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])
        ->name('password.request');

    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])
        ->name('password.email');

    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('/reset-password', [ResetPasswordController::class, 'store'])
        ->name('password.update');

    Route::get('/register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->name('register.store');
});


Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');


/*
|--------------------------------------------------------------------------
| Profile Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');
});


/*
|--------------------------------------------------------------------------
| Administrator / Staff Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active', 'role:admin,staff'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');


    /*
    |--------------------------------------------------------------------------
    | User Management
    |--------------------------------------------------------------------------
    */

    Route::get('/admin/users', [UserController::class, 'index'])
        ->name('admin.users.index');

    Route::patch('/admin/users/{user}/status', [UserController::class, 'updateStatus'])
        ->name('admin.users.status');


    /*
    |--------------------------------------------------------------------------
    | Subscribers
    |--------------------------------------------------------------------------
    */

    Route::get('/admin/subscribers', [SubscriberController::class, 'index'])
        ->name('admin.subscribers.index');

    Route::get('/admin/subscribers/{subscriber}', [SubscriberController::class, 'show'])
        ->name('admin.subscribers.show');

    Route::patch('/admin/subscribers/{subscriber}/status', [SubscriberController::class, 'updateStatus'])
        ->name('admin.subscribers.status');


    /*
    |--------------------------------------------------------------------------
    | Service Applications
    |--------------------------------------------------------------------------
    */

    Route::get('/admin/applications', [AdminServiceApplicationController::class, 'index'])
        ->name('admin.applications.index');

    Route::get('/admin/applications/{application}', [AdminServiceApplicationController::class, 'show'])
        ->name('admin.applications.show');

    Route::post('/admin/applications/{application}/approve', [AdminServiceApplicationController::class, 'approve'])
        ->name('admin.applications.approve');

    Route::post('/admin/applications/{application}/reject', [AdminServiceApplicationController::class, 'reject'])
        ->name('admin.applications.reject');


    /*
    |--------------------------------------------------------------------------
    | Hero Slides
    |--------------------------------------------------------------------------
    */

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('hero-slides', HeroSlideController::class)
            ->except('show');
    });
});


/*
|--------------------------------------------------------------------------
| Technician Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active', 'role:technician'])->group(function () {
    Route::get('/technician/dashboard', [TechnicianDashboardController::class, 'index'])
        ->name('technician.dashboard');
});


/*
|--------------------------------------------------------------------------
| Customer Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active', 'role:customer'])->group(function () {
    Route::get('/customer/dashboard', [CustomerDashboardController::class, 'index'])
        ->name('customer.dashboard');

    Route::get('/customer/application', [ServiceApplicationController::class, 'create'])
        ->name('customer.application.create');

    Route::post('/customer/application', [ServiceApplicationController::class, 'store'])
        ->name('customer.application.store');
});
