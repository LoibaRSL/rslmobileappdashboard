<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WSO2Controller;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\RoleManagementController;
use App\Http\Controllers\WSO2AuthController;



// WSO2 Authentication Routes
Route::prefix('auth')->name('auth.')->group(function () {
    Route::get('wso2', [WSO2AuthController::class, 'redirectToWSO2'])->name('wso2.login');
    Route::get('wso2/callback', [WSO2AuthController::class, 'handleWSO2Callback'])->name('wso2.callback');
    Route::post('logout', [WSO2AuthController::class, 'logout'])->name('logout');
});

// Login route (redirects to WSO2)
Route::get('/login', function () {
    return redirect()->route('auth.wso2.login');
})->name('login');

   Route::get('/', function () {
    return view('auth-split.sign-in');
});

// Protected Routes (require WSO2 authentication)
Route::middleware(['wso2.auth'])->group(function () {

    
 
    // Dashboard
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    
    // Admin routes with permission checks
    Route::prefix('admin')->name('admin.')->middleware(['check.permission:view_dashboard'])->group(function () {
        
        // User Management Routes
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/assign-roles', [UserManagementController::class, 'assignRoles'])->name('users.assign-roles');
        Route::post('/users/{user}/toggle-status', [UserManagementController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/sync-wso2', [UserManagementController::class, 'syncFromWSO2'])->name('users.sync-wso2');
        
        // Role Management Routes
        Route::resource('roles', RoleManagementController::class);
    });
});






Route::get('/auth/delete-account', function () {
    return view('auth.delete-account');
});


Route::get('/auth/reset-pass', function () {
    return view('auth.reset-pass');
});

Route::get('/auth/success-mail', function () {
    return view('auth.success-mail');
});

Route::get('/auth/two-factor', function () {
    return view('auth.two-factor');
});

Route::get('/auth/login-pin', function () {
    return view('auth.login-pin');
});

Route::get('/layouts/compact', function () {
    return view('layouts.compact');
});

Route::get('/layouts/boxed', function () {
    return view('layouts.boxed');
});

Route::get('/layouts/horizontal', function () {
    return view('layouts.horizontal');
});

Route::get('/layouts/preloader', function () {
    return view('layouts.preloader');
});

Route::get('/layouts/scrollable', function () {
    return view('layouts.scrollable');
});

Route::get('/error/400', function () {
    return view('error.400');
});

Route::get('/error/500', function () {
    return view('error.500');
});

Route::get('/error/408', function () {
    return view('error.408');
});

Route::get('/error/404', function () {
    return view('error.404');
});

Route::get('/error/401', function () {
    return view('error.401');
});

Route::get('/error/maintenance', function () {
    return view('error.maintenance');
});

Route::get('/error/403', function () {
    return view('error.403');
});

Route::get('/icons/lucide', function () {
    return view('icons.lucide');
});

Route::get('/auth-split/lock-screen', function () {
    return view('auth-split.lock-screen');
});

Route::get('/auth-split/sign-in', function () {
    return view('auth-split.sign-in');
});

Route::get('/auth-split/new-pass', function () {
    return view('auth-split.new-pass');
});

Route::get('/auth-split/delete-account', function () {
    return view('auth-split.delete-account');
});

Route::get('/auth-split/sign-up', function () {
    return view('auth-split.sign-up');
});

Route::get('/auth-split/reset-pass', function () {
    return view('auth-split.reset-pass');
});

Route::get('/auth-split/success-mail', function () {
    return view('auth-split.success-mail');
});

Route::get('/auth-split/two-factor', function () {
    return view('auth-split.two-factor');
});

Route::get('/auth-split/login-pin', function () {
    return view('auth-split.login-pin');
});

Route::get('/pages/empty', function () {
    return view('pages.empty');
});

Route::get('/layouts/sidebar/dark', function () {
    return view('layouts.sidebar.dark');
});

Route::get('/layouts/sidebar/offcanvas', function () {
    return view('layouts.sidebar.offcanvas');
});

Route::get('/layouts/sidebar/no-icons', function () {
    return view('layouts.sidebar.no-icons');
});

Route::get('/layouts/sidebar/compact', function () {
    return view('layouts.sidebar.compact');
});

Route::get('/layouts/sidebar/image', function () {
    return view('layouts.sidebar.image');
});

Route::get('/layouts/sidebar/on-hover', function () {
    return view('layouts.sidebar.on-hover');
});

Route::get('/layouts/sidebar/gray', function () {
    return view('layouts.sidebar.gray');
});

Route::get('/layouts/sidebar/gradient', function () {
    return view('layouts.sidebar.gradient');
});

Route::get('/layouts/sidebar/on-hover-active', function () {
    return view('layouts.sidebar.on-hover-active');
});

Route::get('/layouts/sidebar/with-lines', function () {
    return view('layouts.sidebar.with-lines');
});

Route::get('/layouts/topbar/gray', function () {
    return view('layouts.topbar.gray');
});

Route::get('/layouts/topbar/gradient', function () {
    return view('layouts.topbar.gradient');
});

Route::get('/layouts/topbar/light', function () {
    return view('layouts.topbar.light');
});


Route::post('/register-tin', [TinRegistrationController::class, 'register']);
Route::post('/business-registration', [BusinessRegistrationController::class, 'store']);
Route::get('/business-registrations', [BusinessRegistrationController::class, 'index']);
Route::get('/business-registration/{id}', [BusinessRegistrationController::class, 'show']);
Route::post('/amend', [BusinessAmendmentController::class, 'store']);

    Route::post('/register', [TinRegistrationsController::class, 'register']);
    Route::post('/verify-email', [TinRegistrationsController::class, 'verifyEmail']);
    Route::get('/status/{ref}', [TinRegistrationsController::class, 'checkStatus']);
    Route::get('/registration/{id}', [TinRegistrationsController::class, 'getRegistration']);
         // Amendment routes
    Route::get('/user-data/{tin}', [TinRegistrationsController::class, 'getUserDataByTin']);
    Route::post('/amend', [TinRegistrationsController::class, 'amend']);
    Route::get('/check-amendment/{tin}', [TinRegistrationsController::class, 'checkPendingAmendment']);


