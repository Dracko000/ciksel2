<?php

namespace Tests\Feature\Guru;

use App\Livewire\Guru\AbsensiKelas;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AbsensiKelasTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsGuru(): User
    {
        $guruUser = User::create([
            'name' => 'Guru Test',
            'email' => 'guru@adms.test',
            'username' => 'guru.test',
            'password' => bcrypt('secret'),
            'role' => 'guru',
        ]);

        $guru = Guru::create([
            'user_id' => $guruUser->id,
            'nip' => '198001012010011001',
            'nama' => 'Guru Test',
        ]);

        // Relasi Auth::user()->guru diambil lewat tabel guru.user_id, jadi
        // tidak ada kolom guru_id di tabel users.
        $this->actingAs($guruUser);

        return $guruUser;
    }

    public function test_kolom_kelas_menampilkan_nama_kelas(): void
    {
        $guru = $this->actingAsGuru();

        $kelas = Kelas::create(['nama_kelas' => '4 A', 'wali_kelas_id' => $guru->guru->id]);

        $user = User::create([
            'name' => 'Siswa Satu',
            'email' => 'siswa1@adms.test',
            'username' => 'siswa1',
            'password' => bcrypt('secret'),
            'role' => 'siswa',
        ]);

        Siswa::create([
            'user_id' => $user->id,
            'nis' => '1101',
            'pin' => '1101',
            'nama' => 'Siswa Satu',
            'kelas_id' => $kelas->id,
        ]);

        // Auth harus tetap sebagai guru, bukan siswa yang dibuat di bawah.
        Livewire::actingAs($guru)
            ->test(AbsensiKelas::class)
            ->assertSee('4 A')
            ->assertSee('Siswa Satu');
    }
}