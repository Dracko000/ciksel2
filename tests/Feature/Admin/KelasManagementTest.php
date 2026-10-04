<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\KelasManagement;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KelasManagementTest extends TestCase
{
    use RefreshDatabase;

    private function makeGuru(string $nip = '1980010100000001'): Guru
    {
        $user = User::create([
            'name' => 'Guru '.substr($nip, -4),
            'email' => $nip.'@adms.local',
            'username' => $nip,
            'password' => bcrypt($nip),
            'role' => 'guru',
        ]);

        return Guru::create([
            'user_id' => $user->id,
            'nip' => $nip,
            'pin' => $nip,
            'nama' => 'Guru '.substr($nip, -4),
        ]);
    }

    private function makeSiswa(string $nis, ?int $kelasId = null): Siswa
    {
        $user = User::create([
            'name' => 'Siswa '.$nis,
            'email' => $nis.'@adms.local',
            'username' => $nis,
            'password' => bcrypt($nis),
            'role' => 'siswa',
        ]);

        return Siswa::create([
            'user_id' => $user->id,
            'nis' => $nis,
            'pin' => $nis,
            'nama' => 'Siswa '.$nis,
            'kelas_id' => $kelasId,
        ]);
    }

    public function test_halaman_menampilkan_daftar_kelas(): void
    {
        $wali = $this->makeGuru();
        $kelas = Kelas::create(['nama_kelas' => '1 A', 'wali_kelas_id' => $wali->id]);
        $this->makeSiswa('212201108', $kelas->id);

        $component = Livewire::test(KelasManagement::class);

        $component->assertSee('1 A')
            ->assertSee('Guru 0001')
            ->assertSee('Roster');
    }

    public function test_bisa_tambah_kelas_dengan_wali(): void
    {
        $wali = $this->makeGuru();

        Livewire::test(KelasManagement::class)
            ->set('namaKelas', '2 B')
            ->set('waliKelasId', $wali->id)
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('kelas', [
            'nama_kelas' => '2 B',
            'wali_kelas_id' => $wali->id,
        ]);
    }

    public function test_nama_kelas_duplikat_ditolak(): void
    {
        Kelas::create(['nama_kelas' => '3 C']);

        Livewire::test(KelasManagement::class)
            ->set('namaKelas', '3 C')
            ->call('store')
            ->assertHasErrors('namaKelas');

        $this->assertSame(1, Kelas::where('nama_kelas', '3 C')->count());
    }

    public function test_ubah_kelas_tidak_menabrak_kelas_lain(): void
    {
        $target = Kelas::create(['nama_kelas' => '4 A']);
        $lain = Kelas::create(['nama_kelas' => '4 B']);

        Livewire::test(KelasManagement::class)
            ->call('edit', $target->id)
            ->set('namaKelas', '4 B')
            ->call('update')
            ->assertHasErrors('namaKelas');

        $this->assertSame('4 A', $target->fresh()->nama_kelas);
        $this->assertSame('4 B', $lain->fresh()->nama_kelas);
    }

    public function test_hapus_kelas_tidak_menghapus_siswa(): void
    {
        $kelas = Kelas::create(['nama_kelas' => '5 A']);
        $siswa = $this->makeSiswa('212201109', $kelas->id);

        Livewire::test(KelasManagement::class)
            ->call('delete', $kelas->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('kelas', ['id' => $kelas->id]);
        $this->assertDatabaseHas('siswa', ['id' => $siswa->id, 'kelas_id' => null]);
    }

    public function test_roster_menampilkan_siswa_kelas_tersebut(): void
    {
        $kelasA = Kelas::create(['nama_kelas' => '6 A']);
        $kelasB = Kelas::create(['nama_kelas' => '6 B']);
        $this->makeSiswa('212201110', $kelasA->id);
        $this->makeSiswa('212201111', $kelasB->id);

        $component = Livewire::test(KelasManagement::class)
            ->call('showRoster', $kelasA->id);

        $roster = $component->viewData('roster');

        $this->assertNotNull($roster);
        $this->assertSame(1, $roster->total());
        $this->assertSame('212201110', $roster->items()[0]->nis);
    }

    public function test_pindah_siswa_ke_kelas_lain(): void
    {
        $asal = Kelas::create(['nama_kelas' => '1 A']);
        $tujuan = Kelas::create(['nama_kelas' => '1 B']);
        $siswa = $this->makeSiswa('212201112', $asal->id);

        Livewire::test(KelasManagement::class)
            ->call('pindahSiswa', $siswa->id, $tujuan->id)
            ->assertHasNoErrors();

        $this->assertSame($tujuan->id, $siswa->fresh()->kelas_id);
    }
}