<?php

use App\Http\Controllers\ScreenshotController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('https://pixashot.com');
});

Route::get('/capture', [ScreenshotController::class, 'capture'])
    ->middleware('timeout:120');
