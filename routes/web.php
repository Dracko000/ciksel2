<?php

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

use App\Http\Controllers\AbsensiExportController;
use App\Http\Controllers\iclockController;
use App\Livewire\Admin\AbsensiMonitor;
use App\Livewire\Admin\AturJamGuru;
use App\Livewire\Admin\AturJamKelas;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\DeviceManagement;
use App\Livewire\Admin\EkstrakulikulerManagement;
use App\Livewire\Admin\InformasiSekolahManagement;
use App\Livewire\Admin\KelasManagement;
use App\Livewire\Admin\KonfirmasiIjin;
use App\Livewire\Admin\LaporanBulanan;
use App\Livewire\Admin\PresensiGuru;
use App\Livewire\Admin\PresensiMurid;
use App\Livewire\Admin\RfidBelumTerdaftar;
use App\Livewire\Admin\UserForm;
use App\Livewire\Admin\UserManagement;
use App\Livewire\Auth\Login;
use App\Livewire\Guru\AbsensiKelasDetail;
use App\Livewire\Guru\InputNilai;
use App\Livewire\Siswa\AbsensiDetail;
use App\Livewire\Siswa\InputIjin;
use Illuminate\Support\Facades\Route;

// Health check ringan untuk monitoring / uptime probe. Tanpa auth dan tanpa
// menyentuh database supaya cepat dan tidak menambah beban.
Route::get('/health', fn () => response('ok', 200))->name('health');

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
        Route::get('/admin/dashboard', Dashboard::class)->name('admin.dashboard');
        Route::get('/admin/users', UserManagement::class)->name('admin.users');
        Route::get('/admin/users/create', UserForm::class)->name('admin.users.create');
        Route::get('/admin/users/{user}/edit', UserForm::class)->name('admin.users.edit');
        Route::get('/admin/kelas', KelasManagement::class)->name('admin.kelas');
        Route::get('/admin/ekstrakulikuler', EkstrakulikulerManagement::class)->name('admin.ekstrakulikuler');
        Route::get('/admin/absensi', AbsensiMonitor::class)->name('admin.absensi');
        Route::get('/admin/absensi/download', AbsensiExportController::class)->name('admin.absensi.download');
        Route::get('/admin/presensi-guru', PresensiGuru::class)->name('admin.presensi-guru');
        Route::get('/admin/presensi-murid', PresensiMurid::class)->name('admin.presensi-murid');
        Route::get('/admin/rfid-belum-terdaftar', RfidBelumTerdaftar::class)->name('admin.rfid');
        Route::get('/admin/jam-guru', AturJamGuru::class)->name('admin.jam-guru');
        Route::get('/admin/jam-kelas', AturJamKelas::class)->name('admin.jam-kelas');
        Route::get('/admin/informasi', InformasiSekolahManagement::class)->name('admin.info');
        Route::get('/admin/konfirmasi', KonfirmasiIjin::class)->name('admin.konfirmasi');
        Route::get('/admin/devices', DeviceManagement::class)->name('admin.devices');
        Route::get('/admin/laporan', LaporanBulanan::class)->name('admin.laporan');
    });

    // Guru Routes
    Route::middleware(['role:guru'])->group(function () {
        Route::get('/guru/dashboard', App\Livewire\Guru\Dashboard::class)->name('guru.dashboard');
        Route::get('/guru/absensi', AbsensiKelasDetail::class)->name('guru.absensi');
        Route::get('/guru/nilai', InputNilai::class)->name('guru.nilai');
    });

    // Siswa Routes
    Route::middleware(['role:siswa'])->group(function () {
        Route::get('/siswa/dashboard', App\Livewire\Siswa\Dashboard::class)->name('siswa.dashboard');
        Route::get('/siswa/izin', InputIjin::class)->name('siswa.izin');
        Route::get('/siswa/absensi', AbsensiDetail::class)->name('siswa.absensi');
    });
});
