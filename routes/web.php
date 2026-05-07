<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WSO2Controller;

Route::get('/', function () {
    return view('index');
});

// WSO2 Authentication Routes (No Middleware)
Route::get('login/wso2', [WSO2Controller::class, 'redirectToProvider'])->name('login.wso2');
Route::get('callback', [WSO2Controller::class, 'handleProviderCallback'])->name('callback');
Route::post('logout/callback', [WSO2Controller::class, 'logout'])->name('logout');

Route::get('/auth/lock-screen', function () {
    return view('auth.lock-screen');
});

Route::get('/auth/sign-in', function () {
    return view('auth.sign-in');
});

Route::get('/auth/new-pass', function () {
    return view('auth.new-pass');
});

Route::get('/auth/delete-account', function () {
    return view('auth.delete-account');
});

Route::get('/auth/sign-up', function () {
    return view('auth.sign-up');
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
