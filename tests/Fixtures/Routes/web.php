<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SampleController;

Route::get('/users', [SampleController::class, 'index'])->name('users.index');
Route::post('/users', [SampleController::class, 'store'])->name('users.store');
Route::resource('/posts', SampleController::class);
