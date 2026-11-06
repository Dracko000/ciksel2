<?php

namespace App\Livewire\Admin;

use App\Models\Kelas;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class UserManagement extends Component
{
    use WithPagination;

    public $kelasList;

    public $search = '';

    public $roleFilter = '';

    public $kelasFilter = '';

    public $perPage = 50;

    /** @var array<int> id user yang dicentang pada tabel. */
    public $selected = [];

    /** Mode konfirmasi hapus: 'single' atau 'bulk'. */
    public $deleteMode = null;

    /** Nama entitas yang dihapus, dipakai untuk teks penjelasan di modal. */
    public $deleteTargetName = '';

    /** Id user yang menunggu konfirmasi hapus. */
    public $deleteId = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'roleFilter' => ['except' => ''],
        'kelasFilter' => ['except' => ''],
    ];

    public function mount()
    {
        $this->kelasList = Kelas::orderBy('nama_kelas')->get();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->selected = [];
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
        $this->selected = [];
    }

    public function updatingKelasFilter(): void
    {
        $this->resetPage();
        $this->selected = [];
    }

    /**
     * Kosongkan pencarian dan semua filter sekaligus.
     */
    public function resetFilter(): void
    {
        $this->search = '';
        $this->roleFilter = '';
        $this->kelasFilter = '';
        $this->resetPage();
        $this->selected = [];
    }

    public function updatingPage(): void
    {
        // Pilihan tidak boleh ikut ke halaman lain:user yang tidak terlihat
        // tidak boleh ikut terhapus.
        $this->selected = [];
    }

    /**
     * Checkbox "pilih semua" hanya berlaku untuk baris yang sedang tampil,
     * supaya admin tidak ohneh menghapus 790 akun tanpa sadar.
     */
    /**
     * Id user yang sedang tampil di halaman ini saja.
     *
     * Dipakai untuk "pilih semua": pada daftar 790 siswa, checkbox header
     * hanya boleh menandai baris yang terlihat. Menandai seluruh hasil
     * query diam-diam adalah bom untuk penghapusan massal.
     */
    protected function visibleIds(): array
    {
        $items = $this->query()->paginate($this->perPage)->items();

        return array_map(static fn ($user) => (int) $user->id, $items);
    }

    public function updatedSelected($value): void
    {
        $visible = $this->visibleIds();

        $this->selected = array_values(array_intersect(
            array_map('intval', (array) $this->selected),
            $visible
        ));
    }

    public function toggleSelectAll(): void
    {
        $visible = $this->visibleIds();

        $this->selected = count($this->selected) === count($visible)
            ? []
            : $visible;
    }

    public function isSelected($id): bool
    {
        return in_array($id, $this->selected, true);
    }

    public function allVisibleSelected(int $visibleCount): bool
    {
        return $visibleCount > 0 && count($this->selected) === $visibleCount;
    }

    /* ------------------------------------------------------------------
     |  Konfirmasi hapus
     |  Hapus tidak dieksekusi dari satu klik, tapi cukup lewat popup
     |  sederhana Hapus/Batal — tidak perlu mengetik ulang nama.
     | ------------------------------------------------------------------ */

    public function askDelete($id): void
    {
        $user = User::with('siswa', 'guru')->findOrFail($id);

        $this->deleteMode = 'single';
        $this->deleteId = $user->id;
        $this->deleteTargetName = $user->name ?? ('user-'.$user->id);
        $this->resetErrorBag();
    }

    public function askBulkDelete(): void
    {
        if ($this->selected === []) {
            return;
        }

        $this->deleteMode = 'bulk';
        $this->deleteId = null;
        $this->deleteTargetName = 'HAPUS';
        $this->resetErrorBag();
    }

    public function cancelDelete(): void
    {
        $this->deleteMode = null;
        $this->deleteId = null;
        $this->deleteTargetName = '';
        $this->resetErrorBag();
    }

    public function confirmDelete(): void
    {
        // Admin tidak boleh menghapus akunnya sendiri lewat layar ini.
        $ids = $this->deleteMode === 'bulk'
            ? $this->selected
            : [$this->deleteId];

        $ids = array_values(array_filter(
            $ids,
            fn ($id) => (int) $id !== (int) auth()->id()
        ));

        if ($ids === []) {
            $this->cancelDelete();
            session()->flash('message', 'Tidak ada akun yang dihapus.');

            return;
        }

        $deleted = User::whereIn('id', $ids)->delete();

        $this->cancelDelete();
        $this->selected = [];

        session()->flash(
            'message',
            $deleted.' akun berhasil dihapus.'
        );
    }

    /**
     * Daftar user tidak dimuat semua sekaligus: sekolah punya ratusan akun dan
     * memuat semuanya akan ikut terkirim ke browser pada setiap permintaan.
     */
    protected function query()
    {
        $search = trim((string) $this->search);
        $like = $search === '' ? null : '%'.addcslashes($search, '%_\\').'%';

        return User::with(['siswa.kelas', 'guru'])
            ->when($like !== null, function ($query) use ($like) {
                $query->where(function ($inner) use ($like) {
                    $inner->where('name', 'like', $like)
                        ->orWhere('username', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhereHas('siswa', fn ($s) => $s->where('nis', 'like', $like))
                        ->orWhereHas('guru', fn ($g) => $g->where('nip', 'like', $like));
                });
            })
            ->when($this->roleFilter !== '', fn ($query) => $query->where('role', $this->roleFilter))
            ->when(
                $this->kelasFilter !== '',
                fn ($query) => $query->whereExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('siswa')
                        ->whereColumn('siswa.user_id', 'users.id')
                        ->where('siswa.kelas_id', $this->kelasFilter);
                })
            )
            ->orderBy('name');
    }

    public function render()
    {
        return view('livewire.admin.user-management', [
            'users' => $this->query()->paginate($this->perPage),
        ])->layout('components.layouts.app');
    }
}
