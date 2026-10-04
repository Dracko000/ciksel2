<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\UserManagement;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function makeSiswa(int $index): User
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

        $this->assertCount(25, $users->items(), 'Halaman pertama harus berisi 25 user.');
        $this->assertSame(60, $users->total(), 'Total user harus dihitung tanpa memuat semuanya.');
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
}