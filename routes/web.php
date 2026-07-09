<?php

declare(strict_types=1);

use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\MasterController;
use App\Http\Controllers\Site\ServiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/services/{service:slug}', [ServiceController::class, 'show'])->name('services.show');
Route::get('/masters/{master:slug}', [MasterController::class, 'show'])->name('masters.show');
