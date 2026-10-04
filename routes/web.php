<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
use App\Http\Controllers\iclockController;
use App\Livewire\Auth\Login;

// Auth Routes
Route::get('/login', Login::class)->name('login');
Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->name('logout');

// ZKTeco ADMS Routes
// Semua kontak device wajib melewati middleware iclock: SN harus terdaftar
// dan, bila device punya secret_token, tokennya harus disertakan.
Route::middleware(['iclock'])->group(function () {
    // handshake
    Route::get('/iclock/cdata', [iclockController::class, 'handshake']);
    // request dari device
    Route::post('/iclock/cdata', [iclockController::class, 'receiveRecords']);
    Route::get('/iclock/getrequest', [iclockController::class, 'getrequest']);
});

Route::get('/iclock/test', [iclockController::class, 'test'])->middleware('iclock');


// Protected Routes
Route::middleware(['auth'])->group(function () {
    // Redirect based on role
    Route::get('/', function () {
        $role = auth()->user()->role;

        return match ($role) {
            'admin' => redirect('/admin/dashboard'),
            'guru' => redirect('/guru/dashboard'),
            default => redirect('/siswa/dashboard'),
        };
    });

    // Admin Routes
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/admin/dashboard', App\Livewire\Admin\Dashboard::class)->name('admin.dashboard');
        Route::get('/admin/users', App\Livewire\Admin\UserManagement::class)->name('admin.users');
        Route::get('/admin/kelas', App\Livewire\Admin\KelasManagement::class)->name('admin.kelas');
        Route::get('/admin/ekstrakulikuler', App\Livewire\Admin\EkstrakulikulerManagement::class)->name('admin.ekstrakulikuler');
        Route::get('/admin/absensi', App\Livewire\Admin\AbsensiMonitor::class)->name('admin.absensi');
        Route::get('/admin/informasi', App\Livewire\Admin\InformasiSekolahManagement::class)->name('admin.info');
        Route::get('/admin/konfirmasi', App\Livewire\Admin\KonfirmasiIjin::class)->name('admin.konfirmasi');
        Route::get('/admin/devices', App\Livewire\Admin\DeviceManagement::class)->name('admin.devices');
        Route::get('/admin/laporan', App\Livewire\Admin\LaporanBulanan::class)->name('admin.laporan');
    });

    // Guru Routes
    Route::middleware(['role:guru'])->group(function () {
        Route::get('/guru/dashboard', App\Livewire\Guru\Dashboard::class)->name('guru.dashboard');
        Route::get('/guru/absensi', App\Livewire\Guru\AbsensiKelas::class)->name('guru.absensi');
        Route::get('/guru/nilai', App\Livewire\Guru\InputNilai::class)->name('guru.nilai');
    });

    // Siswa Routes
    Route::middleware(['role:siswa'])->group(function () {
        Route::get('/siswa/dashboard', App\Livewire\Siswa\Dashboard::class)->name('siswa.dashboard');
        Route::get('/siswa/izin', App\Livewire\Siswa\InputIjin::class)->name('siswa.izin');
    });
});
