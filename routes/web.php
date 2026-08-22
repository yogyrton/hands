<?php

declare(strict_types=1);

use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\MasterController;
use App\Http\Controllers\Site\PolicyController;
use App\Http\Controllers\Site\ServiceController;
use App\Http\Controllers\Site\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/services/{service:slug}', [ServiceController::class, 'show'])->name('services.show');
Route::get('/masters/{master:slug}', [MasterController::class, 'show'])->name('masters.show');
Route::get('/privacy-policy', [PolicyController::class, 'privacy'])->name('privacy');
Route::get('/cookie-policy', [PolicyController::class, 'cookie'])->name('cookie');
Route::get('/certificate-policy', [PolicyController::class, 'certificate'])->name('certificate');
