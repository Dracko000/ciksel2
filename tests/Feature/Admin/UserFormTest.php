<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\UserForm;
use App\Models\Kelas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Form tambah/ubah pengguna kini berada di halaman sendiri, terpisah dari
 * daftar pengguna. Test ini menjaga alur tersebut tetap utuh.
 */
class UserFormTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $admin = User::create([
            'name' => 'Admin Tester',
            'email' => 'admin@form.test',
            'username' => 'admin_form',
            'password' => bcrypt('secret1234'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin);

        return $admin;
    }

    public function test_halaman_tambah_pengguna_tampil_terpisah_dari_daftar(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('Tambah Pengguna')
            ->assertSee('Data akun');

        // Daftar pengguna tidak lagi memuat formulir tambah.
        $this->get(route('admin.users'))
            ->assertOk()
            ->assertSee('Tambah Pengguna')
            ->assertDontSee('Data akun');

        // Halaman ubah memakai komponen yang sama lewat route model binding.
        $target = User::create([
            'name' => 'Target Ubah',
            'email' => 'target@form.test',
            'username' => 'target',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
        ]);

        $this->get(route('admin.users.edit', $target))
            ->assertOk()
            ->assertSee('Ubah Pengguna')
            ->assertSee('Target Ubah');
    }

    public function test_admin_membuat_siswa_baru(): void
    {
        $this->actingAsAdmin();
        $kelas = Kelas::create(['nama_kelas' => '4 A']);

        Livewire::test(UserForm::class)
            ->set('name', 'Siswa Baru')
            ->set('email', 'siswa.baru@sekolah.test')
            ->set('role', 'siswa')
            ->set('nis', '2210900123')
            ->set('kelas_id', $kelas->id)
            ->call('simpan')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.users'));

        $user = User::where('email', 'siswa.baru@sekolah.test')->firstOrFail();

        $this->assertSame('siswa', $user->role);
        $this->assertSame('2210900123', $user->username);
        $this->assertTrue(Hash::check('2210900123', $user->password), 'Password awal harus sama dengan username.');

        $this->assertDatabaseHas('siswa', [
            'user_id' => $user->id,
            'nis' => '2210900123',
            'kelas_id' => $kelas->id,
        ]);
    }

    public function test_admin_membuat_guru_dengan_nip(): void
    {
        $this->actingAsAdmin();

        Livewire::test(UserForm::class)
            ->set('name', 'Guru Baru')
            ->set('email', 'guru.baru@sekolah.test')
            ->set('role', 'guru')
            ->set('nip', '1987654321')
            ->set('is_tendik', true)
            ->call('simpan')
            ->assertHasNoErrors();

        $user = User::where('email', 'guru.baru@sekolah.test')->firstOrFail();

        $this->assertSame('1987654321', $user->username);
        $this->assertDatabaseHas('guru', [
            'user_id' => $user->id,
            'nip' => '1987654321',
            'is_tendik' => 1,
        ]);
    }

    public function test_rfid_dari_halaman_asing_terisi_otomatis(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.users.create', ['rfid' => '9001']))
            ->assertOk()
            ->assertSee('Terisi dari halaman RFID Belum Terdaftar');

        Livewire::withQueryParams(['rfid' => '9001'])
            ->test(UserForm::class)
            ->assertSet('wajah_id_zkteco', '9001')
            ->set('name', 'Pemilik RFID')
            ->set('email', 'pemilik.rfid@sekolah.test')
            ->set('role', 'siswa')
            ->set('nis', '2210900999')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('siswa', [
            'nis' => '2210900999',
            'wajah_id_zkteco' => '9001',
        ]);
    }

    public function test_ubah_pengguna_tanpa_mengganti_password(): void
    {
        $this->actingAsAdmin();

        $user = User::create([
            'name' => 'Nama Lama',
            'email' => 'lama@sekolah.test',
            'username' => '2210900001',
            'password' => bcrypt('passwordlama'),
            'role' => 'siswa',
        ]);
        $user->siswa()->create([
            'user_id' => $user->id,
            'nis' => '2210900001',
            'pin' => '2210900001',
            'nama' => 'Nama Lama',
        ]);

        Livewire::test(UserForm::class, ['user' => $user])
            ->assertSet('isEdit', true)
            ->assertSet('name', 'Nama Lama')
            ->set('name', 'Nama Baru')
            ->call('simpan')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.users'));

        $user->refresh();

        $this->assertSame('Nama Baru', $user->name);
        $this->assertSame('Nama Baru', $user->siswa->nama);
        $this->assertTrue(Hash::check('passwordlama', $user->password), 'Password tidak boleh berubah bila kosong.');
    }

    public function test_nis_ganda_ditolak(): void
    {
        $this->actingAsAdmin();

        $lain = User::create([
            'name' => 'Siswa Lama',
            'email' => 'lama2@sekolah.test',
            'username' => '2210900002',
            'password' => bcrypt('secret123'),
            'role' => 'siswa',
        ]);
        $lain->siswa()->create([
            'user_id' => $lain->id,
            'nis' => '2210900002',
            'pin' => '2210900002',
            'nama' => 'Siswa Lama',
        ]);

        Livewire::test(UserForm::class)
            ->set('name', 'Siswa Ganda')
            ->set('email', 'ganda@sekolah.test')
            ->set('role', 'siswa')
            ->set('nis', '2210900002')
            ->call('simpan')
            ->assertHasErrors(['nis']);

        $this->assertDatabaseMissing('users', ['email' => 'ganda@sekolah.test']);
    }

    public function test_bukan_admin_tidak_bisa_membuka_form(): void
    {
        $guru = User::create([
            'name' => 'Guru',
            'email' => 'guru@sekolah.test',
            'username' => 'guru',
            'password' => bcrypt('secret123'),
            'role' => 'guru',
        ]);

        $this->actingAs($guru)
            ->get(route('admin.users.create'))
            ->assertForbidden();
    }
}
