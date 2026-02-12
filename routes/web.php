<?php

use App\Http\Controllers\DNSController;
use App\Http\Controllers\PrintController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', [DNSController::class, 'index'])->name('dns');

Route::post('/print', [PrintController::class, 'index'])->name('print');

require __DIR__.'/settings.php';
