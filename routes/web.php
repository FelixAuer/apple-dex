<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/sw.js', function () {
    $manifestPath = public_path('build/manifest.json');
    $manifest = json_decode(file_get_contents($manifestPath), true);

    $assets = collect($manifest)
        ->pluck('file')
        ->map(fn ($file) => '/build/'.$file)
        ->push('/manifest.json')
        ->push('/images/icon-192.png')
        ->push('/images/icon-512.png')
        ->push('/images/apple-silhouette.svg')
        ->values()
        ->all();

    $version = substr(md5(file_get_contents($manifestPath)), 0, 12);

    return response()
        ->view('sw', ['assets' => $assets, 'version' => $version])
        ->header('Content-Type', 'application/javascript');
})->name('service-worker');

Route::middleware(['auth'])->group(function () {
    Volt::route('/', 'dex')->name('dex');
    Volt::route('/varieties/{variety}', 'variety-card')->name('varieties.show');
    Volt::route('/catch/new', 'new-catch')->name('catch.new');

    Route::view('profile', 'profile')->name('profile');
});

require __DIR__.'/auth.php';
