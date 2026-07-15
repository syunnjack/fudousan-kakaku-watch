<?php

use App\Http\Controllers\AreaWatchController;
use App\Http\Controllers\LineLoginController;
use App\Http\Controllers\LineWebhookController;
use App\Http\Controllers\RentReportController;
use App\Http\Controllers\RentWatchController;
use App\Http\Controllers\WatchController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AreaWatchController::class, 'index'])->name('watch.index');
Route::get('/search', [AreaWatchController::class, 'search'])->name('watch.search');
Route::get('/sitemap.xml', [AreaWatchController::class, 'sitemap'])->name('sitemap');
Route::view('/about', 'about')->name('about');

// 家賃口コミ（賃貸事例比較法的なアプローチによる実測家賃の収集）
Route::get('/rent', [RentReportController::class, 'index'])->name('rent.index');
Route::get('/rent/search', [RentReportController::class, 'search'])->name('rent.search');
Route::post('/rent/reports', [RentReportController::class, 'store'])
    ->name('rent.reports.store')
    ->middleware('throttle:5,1');
Route::post('/rent/watches', [RentWatchController::class, 'toggle'])
    ->name('rent.watches.toggle')
    ->middleware('throttle:10,1');

// LINE連携（ウォッチ都道府県の不動産取引価格の大幅変動通知／新着家賃口コミ通知）
Route::get('/line/login', [LineLoginController::class, 'redirect'])->name('line.login');
Route::get('/line/callback', [LineLoginController::class, 'callback'])->name('line.callback');
Route::post('/watches', [WatchController::class, 'toggle'])
    ->name('watches.toggle')
    ->middleware('throttle:10,1');
Route::post('/line/webhook', [LineWebhookController::class, 'handle'])->name('line.webhook');
