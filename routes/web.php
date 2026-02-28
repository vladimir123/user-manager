<?php

use App\Http\Controllers\ImportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/users');

Route::resource('users', UserController::class);

Route::post('/import', ImportController::class)->name('import');
