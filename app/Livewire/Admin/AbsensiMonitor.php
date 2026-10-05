<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use App\Models\Siswa;
use App\Models\Guru;
use App\Models\Kelas;

class AbsensiMonitor extends Component
{
    use WithPagination;

    public $dateFilter;

    /**
     * Filter kelas. String kosong berarti semua kelas.
     */
    public $kelasFilter = '';

    /**
     * Filter peran yang teridentifikasi dari tap. String kosong = semua.
     */
    public $roleFilter = '';

    public function mount()
    {
        $this->dateFilter = date('Y-m-d');
    }

    /**
     * Ganti tanggal atau kelas mengembalikan ke halaman 1, supaya filter
     * tidak menyisakan nomor halaman yang sudah tidak ada isinya.
     */
    public function updatingDateFilter(): void
    {
        $this->resetPage();
    }

    public function updatingKelasFilter(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function resetFilter(): void
    {
        $this->kelasFilter = '';
        $this->roleFilter = '';
        $this->resetPage();
    }

    public function resetKelasFilter()
    {
        $this->kelasFilter = '';
        $this->resetPage();
    }

    public function render()
    {
        // Absensi di-linking ke siswa lewat wajah_id_zkteco (ZKTeco ID),
        // bukan lewat user_id, jadi filter kelas harus diterjemahkan ke
        // daftar ZKTeco ID milik siswa di kelas tersebut.
        $employeeIdsKelas = null;

        if ($this->kelasFilter !== '' && $this->kelasFilter !== null) {
            $employeeIdsKelas = Siswa::where('kelas_id', $this->kelasFilter)
                ->whereNotNull('wajah_id_zkteco')
                ->pluck('wajah_id_zkteco')
                ->all();
        }

        // Kolom "Teridentifikasi Sebagai" bisa berisi siswa, guru/tendik,
        // atau tap dari wajah yang tidak terdaftar. Filter peran memakai
        // ZKTeco ID masing-masing kelompok; tap tak dikenal ikut tersaring
        // keluar begitu role tertentu dipilih.
        $employeeIdsRole = null;

        if ($this->roleFilter === 'siswa') {
            $employeeIdsRole = Siswa::whereNotNull('wajah_id_zkteco')
                ->pluck('wajah_id_zkteco')
                ->all();
        } elseif ($this->roleFilter === 'guru') {
            $employeeIdsRole = Guru::whereNotNull('wajah_id_zkteco')
                ->pluck('wajah_id_zkteco')
                ->all();
        }

        $attendances = DB::table('attendances')
            ->whereDate('timestamp', $this->dateFilter)
            ->when(
                $employeeIdsKelas !== null,
                fn ($query) => $query->whereIn('employee_id', $employeeIdsKelas)
            )
            ->when(
                $employeeIdsRole !== null,
                fn ($query) => $query->whereIn('employee_id', $employeeIdsRole)
            )
            ->orderBy('timestamp', 'DESC')
            ->paginate(20);

        // Map employee_id ke Siswa/Guru Name
        $employeeIds = $attendances->pluck('employee_id')->unique()->toArray();

        $siswas = Siswa::with('kelas')
            ->whereIn('wajah_id_zkteco', $employeeIds)
            ->get()
            ->keyBy('wajah_id_zkteco');
        $gurus = Guru::whereIn('wajah_id_zkteco', $employeeIds)->get()->keyBy('wajah_id_zkteco');

        // Hanya kelas yang siswanya sudah terdaftar di mesin absensi, supaya
        // pilihan filter tidak menghasilkan tabel kosong tanpa alasan.
        $kelasList = Kelas::whereHas('siswa', fn ($query) => $query->whereNotNull('wajah_id_zkteco'))
            ->orderBy('nama_kelas')
            ->get(['id', 'nama_kelas']);

        return view('livewire.admin.absensi-monitor', [
            'attendances' => $attendances,
            'siswas' => $siswas,
            'gurus' => $gurus,
            'kelasList' => $kelasList,
        ])->layout('components.layouts.app');
    }
}