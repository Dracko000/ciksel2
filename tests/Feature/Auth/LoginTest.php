<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\Login;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_bisa_login_dengan_username_sebagai_password(): void
    {
        $user = User::create([
            'name' => 'Administrator',
            'email' => 'admin@sdn.com',
            'username' => 'administrator',
            'password' => bcrypt('administrator'),
            'role' => 'admin',
        ]);

        Livewire::test(Login::class)
            ->set('username', 'administrator')
            ->set('password', 'administrator')
            ->call('login')
            ->assertRedirect('/admin/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_siswa_bisa_login_dengan_nis_sebagai_password(): void
    {
        $nis = '212201108';

        $user = User::create([
            'name' => 'Anindita Raffa Pathina Khairiniswa',
            'email' => $nis.'@adms.local',
            'username' => $nis,
            'password' => bcrypt($nis),
            'role' => 'siswa',
        ]);

        Siswa::create([
            'user_id' => $user->id,
            'nis' => $nis,
            'pin' => $nis,
            'nama' => 'Anindita Raffa Pathina Khairiniswa',
        ]);

        Livewire::test(Login::class)
            ->set('username', $nis)
            ->set('password', $nis)
            ->call('login')
            ->assertRedirect('/siswa/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_password_salah_ditolak(): void
    {
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@sdn.com',
            'username' => 'administrator',
            'password' => bcrypt('administrator'),
            'role' => 'admin',
        ]);

        Livewire::test(Login::class)
            ->set('username', 'administrator')
            ->set('password', 'password-salah')
            ->call('login')
            ->assertHasErrors('username');

        $this->assertGuest();
    }
}