<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\UserManagement;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function makeSiswa(int $index, ?Kelas $kelas = null): User
    {
        $nis = '22'.str_pad((string) $index, 8, '0', STR_PAD_LEFT);

        $user = User::create([
            'name' => 'Siswa '.$index,
            'email' => $nis.'@adms.local',
            'username' => $nis,
            'password' => bcrypt($nis),
            'role' => 'siswa',
        ]);

        Siswa::create([
            'user_id' => $user->id,
            'nis' => $nis,
            'pin' => $nis,
            'nama' => 'Siswa '.$index,
            'kelas_id' => $kelas?->id,
        ]);

        return $user;
    }

    public function test_daftar_user_di_paginasi_bukan_dimuat_semua(): void
    {
        foreach (range(1, 60) as $index) {
            $this->makeSiswa($index);
        }

        $component = Livewire::test(UserManagement::class);

        $users = $component->viewData('users');

        $this->assertCount(50, $users->items(), 'Halaman pertama harus berisi 50 user.');
        $this->assertSame(60, $users->total(), 'Total user harus dihitung tanpa memuat semuanya.');

        // Jumlah baris tetap bisa diubah admin lewat dropdown per halaman.
        $component->set('perPage', 25);

        $this->assertCount(
            25,
            $component->viewData('users')->items(),
            'Dropdown per halaman harus mengubah jumlah baris.'
        );
    }

    public function test_pencarian_menyaring_berdasarkan_nama(): void
    {
        foreach (range(1, 30) as $index) {
            $this->makeSiswa($index);
        }

        $component = Livewire::test(UserManagement::class)
            ->set('search', 'Siswa 7');

        $users = $component->viewData('users');

        $this->assertGreaterThan(0, $users->total());
        $this->assertLessThan(30, $users->total(), 'Pencarian harus mempersempit hasil.');

        foreach ($users->items() as $user) {
            $this->assertStringContainsString('Siswa 7', $user->name);
        }
    }

    public function test_filter_role_menyaring_berdasarkan_role(): void
    {
        foreach (range(1, 5) as $index) {
            $this->makeSiswa($index);
        }

        User::create([
            'name' => 'Administrator',
            'email' => 'admin@sdn.com',
            'username' => 'administrator',
            'password' => bcrypt('administrator'),
            'role' => 'admin',
        ]);

        $users = Livewire::test(UserManagement::class)
            ->set('roleFilter', 'admin')
            ->viewData('users');

        $this->assertSame(1, $users->total());

        foreach ($users->items() as $user) {
            $this->assertSame('admin', $user->role);
        }
    }

    public function test_filter_kelas_menyaring_siswa_secara_kelas(): void
    {
        $kelasA = Kelas::create(['nama_kelas' => '4 A']);
        $kelasB = Kelas::create(['nama_kelas' => '4 B']);

        $this->makeSiswa(1, $kelasA);
        $this->makeSiswa(2, $kelasB);
        $this->makeSiswa(3, $kelasA);

        $component = Livewire::test(UserManagement::class);

        $this->assertSame(3, $component->viewData('users')->total());

        $users = $component->set('kelasFilter', $kelasA->id)->viewData('users');

        $this->assertSame(2, $users->total());

        foreach ($users->items() as $user) {
            $this->assertSame($kelasA->id, $user->siswa->kelas_id);
        }
    }

    public function test_filter_kelas_menyisihkan_admin_dan_guru(): void
    {
        $kelas = Kelas::create(['nama_kelas' => '5 C']);
        $this->makeSiswa(1, $kelas);

        // Guru dan admin tidak punya kelas, jadi memilih kelas harus
        // menyingkirkan keduanya dari hasil.
        foreach ([
            ['guru', '8001@adms.local', '8001'],
            ['admin', 'admin@adms.local', 'administrator'],
        ] as [$role, $email, $username]) {
            User::create([
                'name' => ucfirst($role),
                'email' => $email,
                'username' => $username,
                'password' => bcrypt('secret'),
                'role' => $role,
            ]);
        }

        $users = Livewire::test(UserManagement::class)
            ->set('kelasFilter', $kelas->id)
            ->viewData('users');

        $this->assertSame(1, $users->total());
        $this->assertSame('siswa', $users->items()[0]->role);
    }

    public function test_filter_kelas_dan_role_bisa_digabung(): void
    {
        $kelas = Kelas::create(['nama_kelas' => '6 A']);
        $this->makeSiswa(1, $kelas);

        User::create([
            'name' => 'Administrator',
            'email' => 'admin@adms.local',
            'username' => 'administrator',
            'password' => bcrypt('secret'),
            'role' => 'admin',
        ]);

        // Kombinasi kelas + role admin tidak mungkin ada.
        $users = Livewire::test(UserManagement::class)
            ->set('roleFilter', 'admin')
            ->set('kelasFilter', $kelas->id)
            ->viewData('users');

        $this->assertSame(0, $users->total());
    }

    public function test_reset_filter_mengosongkan_pencarian_role_dan_kelas(): void
    {
        $kelas = Kelas::create(['nama_kelas' => '6 B']);
        $this->makeSiswa(1, $kelas);

        Livewire::test(UserManagement::class)
            ->set('search', 'Siswa')
            ->set('roleFilter', 'siswa')
            ->set('kelasFilter', $kelas->id)
            ->call('resetFilter')
            ->assertSet('search', '')
            ->assertSet('roleFilter', '')
            ->assertSet('kelasFilter', '');
    }

    public function test_hapus_satu_akun_wajib_mengetik_nama(): void
    {
        $admin = $this->actingAsAdmin();
        $target = $this->makeSiswa(1);

        $component = Livewire::test(UserManagement::class)
            ->call('askDelete', $target->id)
            ->assertSet('deleteMode', 'single')
            ->assertSet('deleteTargetName', 'Siswa 1');

        // Teks salah => belum terhapus.
        $component->set('deleteConfirmText', 'Siswa 999')
            ->call('confirmDelete')
            ->assertHasErrors('deleteConfirmText');

        $this->assertDatabaseHas('users', ['id' => $target->id]);

        // Teks benar => terhapus.
        $component->set('deleteConfirmText', 'Siswa 1')
            ->call('confirmDelete')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_hapus_massal_wajib_mengetik_kata_hapus(): void
    {
        $this->actingAsAdmin();

        $a = $this->makeSiswa(1);
        $b = $this->makeSiswa(2);
        $c = $this->makeSiswa(3);

        $component = Livewire::test(UserManagement::class)
            ->set('selected', [$a->id, $b->id])
            ->call('askBulkDelete')
            ->assertSet('deleteMode', 'bulk')
            ->assertSet('deleteTargetName', 'HAPUS');

        // Teks salah => belum ada yang terhapus.
        $component->set('deleteConfirmText', 'hapus semua')
            ->call('confirmDelete')
            ->assertHasErrors('deleteConfirmText');

        $this->assertDatabaseHas('users', ['id' => $a->id]);
        $this->assertDatabaseHas('users', ['id' => $b->id]);

        $component->set('deleteConfirmText', 'HAPUS')
            ->call('confirmDelete')
            ->assertHasNoErrors()
            ->assertSet('selected', []);

        $this->assertDatabaseMissing('users', ['id' => $a->id]);
        $this->assertDatabaseMissing('users', ['id' => $b->id]);
        $this->assertDatabaseHas('users', ['id' => $c->id]);
    }

    public function test_admin_tidak_bisa_menghapus_akunnya_sendiri(): void
    {
        $admin = $this->actingAsAdmin();
        $other = $this->makeSiswa(1);

        Livewire::test(UserManagement::class)
            ->set('selected', [$admin->id, $other->id])
            ->call('askBulkDelete')
            ->set('deleteConfirmText', 'HAPUS')
            ->call('confirmDelete')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertDatabaseMissing('users', ['id' => $other->id]);
    }

    public function test_pilih_semua_hanya_meliput_baris_yang_tampil(): void
    {
        $this->actingAsAdmin();

        foreach (range(1, 5) as $index) {
            $this->makeSiswa($index);
        }

        // perPage 2 supaya hanya 2 dari 5 yang terlihat di halaman 1.
        $component = Livewire::test(UserManagement::class)->set('perPage', 2);

        $component->call('toggleSelectAll');

        $this->assertCount(2, $component->get('selected'), 'Pilih semua tidak boleh melebar ke halaman lain.');
    }

    public function test_paginasi_memakai_warna_brand(): void
    {
        $this->actingAsAdmin();

        foreach (range(1, 60) as $index) {
            $this->makeSiswa($index);
        }

        $html = Livewire::test(UserManagement::class)->html();

        // Override paginasi Livewire harus aktif: angka halaman memakai
        // brand-600, bukan abu-abu/biru bawaan.
        $this->assertStringContainsString('bg-brand-600', $html, 'Angka halaman aktif harus memakai brand-600.');
        $this->assertStringContainsString('aria-current="page"', $html);

        // Baris info "Menampilkan X-Y dari Z" juga wajib ada.
        $this->assertStringContainsString('Menampilkan', $html);
        $this->assertStringContainsString('dari', $html);
    }

    public function test_klik_pindah_halaman_menampilkan_data_halaman_berikutnya(): void
    {
        $this->actingAsAdmin();

        foreach (range(1, 60) as $index) {
            $this->makeSiswa($index);
        }

        // Urutan baris mengikuti sorting server, jadi jangan diasumsikan
        // siswa ke-51 = baris ke-51. Hitung baris lewat wire:key-nya.
        $baris = fn (string $html): array => preg_match_all('/wire:key="user-(\d+)"/', $html, $m)
            ? $m[1]
            : [];

        $component = Livewire::test(UserManagement::class);

        $barisHalamanSatu = $baris($component->html());
        $this->assertCount(50, $barisHalamanSatu, 'Halaman 1 harus berisi 50 baris.');

        // Simulasikan klik tombol "2" pada paginasi.
        $component->call('gotoPage', 2, 'page')->assertHasNoErrors();

        $barisHalamanDua = $baris($component->html());
        $this->assertNotEmpty($barisHalamanDua, 'Halaman 2 harus berisi data.');
        $this->assertEmpty(
            array_intersect($barisHalamanSatu, $barisHalamanDua),
            'Halaman 2 tidak boleh menampilkan baris yang sama dengan halaman 1.'
        );

        // Tombol halaman 2 kini harus jadi penanda halaman aktif.
        $this->assertStringContainsString('aria-current="page"', $component->html());

        // Tombol halaman 2 kini harus jadi penanda halaman aktif.
        $this->assertStringContainsString('aria-current="page"', $component->html());
    }

    private function actingAsAdmin(): User
    {
        $admin = User::create([
            'name' => 'Admin Tester',
            'email' => 'admin@tester.local',
            'username' => 'admin_tester',
            'password' => bcrypt('secret1234'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin);

        return $admin;
    }
}