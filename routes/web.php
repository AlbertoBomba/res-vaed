<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PublicCourtController;
use App\Livewire\Courts\Editor;
use App\Livewire\Courts\Index;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicCourtController::class, 'index'])->name('courts.index');
Route::get('/reservar/{court}', [BookingController::class, 'create'])->name('bookings.create');
Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function () {
    Route::post('/reservar/{court}', [BookingController::class, 'store'])->name('bookings.store');
    Route::get('/mis-reservas', [BookingController::class, 'index'])->name('bookings.index');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/admin', [DashboardController::class, 'admin'])->name('admin.dashboard');
    Route::get('/mi-cuenta', [DashboardController::class, 'customer'])->name('customer.dashboard');
    Route::get('/admin/pistas', Index::class)->name('admin.courts.index');
    Route::get('/admin/pistas/crear', Editor::class)->name('admin.courts.create');
    Route::get('/admin/pistas/{court}/editar', Editor::class)->whereNumber('court')->name('admin.courts.edit');
});
