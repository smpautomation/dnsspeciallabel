<?php

use App\Http\Controllers\DNSController;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', [DNSController::class, 'index'])->name('dns');

Route::post('/print', [PrintController::class, 'index'])->name('print');

Route::post('/settings/update', [SettingsController::class, 'update'])->name('settings.update');

require __DIR__.'/settings.php';
