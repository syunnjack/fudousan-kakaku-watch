<?php

use App\Http\Controllers\AreaWatchController;
use App\Http\Controllers\LineLoginController;
use App\Http\Controllers\LineWebhookController;
use App\Http\Controllers\WatchController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AreaWatchController::class, 'index'])->name('watch.index');
Route::get('/search', [AreaWatchController::class, 'search'])->name('watch.search');
Route::get('/sitemap.xml', [AreaWatchController::class, 'sitemap'])->name('sitemap');
Route::view('/about', 'about')->name('about');

// LINE連携（ウォッチ都道府県の不動産取引価格の大幅変動通知）
Route::get('/line/login', [LineLoginController::class, 'redirect'])->name('line.login');
Route::get('/line/callback', [LineLoginController::class, 'callback'])->name('line.callback');
Route::post('/watches', [WatchController::class, 'toggle'])
    ->name('watches.toggle')
    ->middleware('throttle:10,1');
Route::post('/line/webhook', [LineWebhookController::class, 'handle'])->name('line.webhook');
