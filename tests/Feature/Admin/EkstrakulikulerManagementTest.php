<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\EkstrakulikulerManagement;
use App\Models\Ekstrakulikuler;
use App\Models\Guru;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class EkstrakulikulerManagementTest extends TestCase
{
    use RefreshDatabase;

    private function makeGuru(string $nip = '1980010100000001'): Guru
    {
        $user = User::create([
            'name' => 'Pembina Satu',
            'email' => $nip.'@adms.local',
            'username' => $nip,
            'password' => bcrypt($nip),
            'role' => 'guru',
        ]);

        return Guru::create([
            'user_id' => $user->id,
            'nip' => $nip,
            'pin' => $nip,
            'nama' => 'Pembina Satu',
        ]);
    }

    private function makeSiswa(string $nis): Siswa
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
        ]);
    }

    public function test_bisa_tambah_ekstrakulikuler_lengkap(): void
    {
        $guru = $this->makeGuru();

        Livewire::test(EkstrakulikulerManagement::class)
            ->set('nama', 'Pramuka')
            ->set('deskripsi', 'Latihan rutin mingguan')
            ->set('guruId', $guru->id)
            ->set('hari', 'Jumat')
            ->set('jamMulai', '14:00')
            ->set('jamSelesai', '16:00')
            ->set('lokasi', 'Lapangan Utama')
            ->set('tahunAjaran', '2026/2027')
            ->set('semester', 'Ganjil')
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('ekstrakulikuler', [
            'nama' => 'Pramuka',
            'guru_id' => $guru->id,
            'hari' => 'Jumat',
            'lokasi' => 'Lapangan Utama',
            'aktif' => 1,
        ]);
    }

    public function test_jam_selesai_harus_setelah_jam_mulai(): void
    {
        Livewire::test(EkstrakulikulerManagement::class)
            ->set('nama', 'Karate')
            ->set('jamMulai', '16:00')
            ->set('jamSelesai', '14:00')
            ->call('store')
            ->assertHasErrors('jamSelesai');

        $this->assertDatabaseCount('ekstrakulikuler', 0);
    }

    public function test_nama_duplikat_ditolak(): void
    {
        Ekstrakulikuler::create(['nama' => 'Robotik']);

        Livewire::test(EkstrakulikulerManagement::class)
            ->set('nama', 'Robotik')
            ->call('store')
            ->assertHasErrors('nama');
    }

    public function test_tambah_dan_lepas_peserta(): void
    {
        $eks = Ekstrakulikuler::create(['nama' => 'Pencak Silat']);
        $siswaA = $this->makeSiswa('212201108');
        $siswaB = $this->makeSiswa('212201109');

        Livewire::test(EkstrakulikulerManagement::class)
            ->call('edit', $eks->id)
            ->set('terpilih', [$siswaA->id, $siswaB->id])
            ->set('tahunAjaran', '2026/2027')
            ->set('semester', 'Ganjil')
            ->call('tambahPeserta')
            ->assertHasNoErrors();

        $this->assertSame(2, $eks->fresh()->siswa()->count());

        Livewire::test(EkstrakulikulerManagement::class)
            ->call('edit', $eks->id)
            ->call('lepasPeserta', $siswaA->id)
            ->assertHasNoErrors();

        $this->assertSame(1, $eks->fresh()->siswa()->count());
        $this->assertDatabaseMissing('ekstrakulikuler_siswa', [
            'ekstrakulikuler_id' => $eks->id,
            'siswa_id' => $siswaA->id,
        ]);
    }

    public function test_siswa_tidak_bisa_terdaftar_dua_kali(): void
    {
        $eks = Ekstrakulikuler::create(['nama' => 'Tari Tradisional']);
        $siswa = $this->makeSiswa('212201110');

        // year/semester kosong (NULL) dulu tidak_unique karena MySQL menganggap
        // setiap NULL berbeda, sehingga siswa bisa muncul dua kali.
        $eks->siswa()->attach($siswa->id);

        $component = Livewire::test(EkstrakulikulerManagement::class)->call('edit', $eks->id);

        // Daftar peserta tidak menampilkan siswa yang sudah terdaftar.
        $nama = collect($component->viewData('siswaList'))->pluck('id');
        $this->assertNotContains($siswa->id, $nama->all());

        // Mendaftarkan lewat UI pun tidak menambah baris kedua.
        Livewire::test(EkstrakulikulerManagement::class)
            ->call('edit', $eks->id)
            ->set('terpilih', [$siswa->id])
            ->call('tambahPeserta');

        $this->assertSame(1, DB::table('ekstrakulikuler_siswa')
            ->where('ekstrakulikuler_id', $eks->id)
            ->where('siswa_id', $siswa->id)
            ->count());
    }

    public function test_database_menolak_pendaftaran_ganda(): void
    {
        $eks = Ekstrakulikuler::create(['nama' => 'Rohis']);
        $siswa = $this->makeSiswa('212201112');

        $eks->siswa()->attach($siswa->id);

        $this->expectException(QueryException::class);

        DB::table('ekstrakulikuler_siswa')->insert([
            'ekstrakulikuler_id' => $eks->id,
            'siswa_id' => $siswa->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_toggle_aktif(): void
    {
        $eks = Ekstrakulikuler::create(['nama' => 'Futsal', 'aktif' => true]);

        Livewire::test(EkstrakulikulerManagement::class)
            ->call('toggleAktif', $eks->id);

        $this->assertFalse((bool) $eks->fresh()->aktif);

        Livewire::test(EkstrakulikulerManagement::class)
            ->call('toggleAktif', $eks->id);

        $this->assertTrue((bool) $eks->fresh()->aktif);
    }

    public function test_hapus_ekstrakulikuler_beserta_pesertanya(): void
    {
        $eks = Ekstrakulikuler::create(['nama' => 'Marching Band']);
        $siswa = $this->makeSiswa('212201111');
        $eks->siswa()->attach($siswa->id);

        // Hapus hanya jalan lewat konfirmasi ketik-nama.
        $component = Livewire::test(EkstrakulikulerManagement::class)
            ->call('askDelete', $eks->id)
            ->assertSet('hapusEkskulId', $eks->id)
            ->assertSet('hapusEkskulNama', 'Marching Band');

        // Salah ketik => kegiatan belum terhapus.
        $component->call('confirmHapusEkskul')->assertHasErrors('hapusEkskulKonfirmasi');
        $this->assertDatabaseHas('ekstrakulikuler', ['id' => $eks->id]);

        // Ketik nama yang benar => baru terhapus.
        $component->set('hapusEkskulKonfirmasi', 'Marching Band')
            ->call('confirmHapusEkskul')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('ekstrakulikuler', ['id' => $eks->id]);
        $this->assertDatabaseMissing('ekstrakulikuler_siswa', ['siswa_id' => $siswa->id]);
    }
}