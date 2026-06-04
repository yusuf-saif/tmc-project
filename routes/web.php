<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('landing'))->name('landing');
Route::get('/offline', fn () => view('offline'))->name('offline');
