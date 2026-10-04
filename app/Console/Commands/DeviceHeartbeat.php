<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Models\Ekstrakulikuler;
use App\Models\Siswa;
use App\Notifications\DeviceOffline;
use App\Notifications\JadwalMendatang;
use App\Support\Notifikasi;
use Illuminate\Console\Command;

/**
 * Menandai mesin yang diam sebagai offline lalu memberi tahu admin, sekaligus
 * mengirim pengingat kegiatan ekstrakulikuler untuk hari berjalan.
 */
class DeviceHeartbeat extends Command
{
    /** Menit tanpa kontak sebelum mesin dianggap offline. */
    private const AMBANG_MENIT = 15;

    protected $signature = 'adms:device-heartbeat {--jadwal : Kirim juga pengingat ekstrakulikuler hari ini}';

    protected $description = 'Tandai device offline yang sudah lama diam dan kirim pengingat jadwal';

    /**
     * Nama hari dalam bahasa Indonesia sesuai enum kolom "hari".
     *
     * Kolom itu hanya menerima Senin sampai Sabtu, jadi Minggu dikembalikan
     * sebagai null karena bukan hari sekolah.
     */
    public static function namaHari(): ?string
    {
        $hari = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ];

        return $hari[now()->format('l')] ?? null;
    }

public function handle(): int
    {
        $this->tandaiOffline();

        // Pengingat jadwal hanya dikirim sekali pagi lewat option --jadwal.
        // Sebelumnya method ini selalu dipanggil, padahal perintahnya dijadwalkan
        // setiap lima menit, sehingga siswa bisa menerima pengingat berulang.
        if ($this->option('jadwal')) {
            $this->kirimPengingatJadwal();
        }

        return self::SUCCESS;
    }

    private function tandaiOffline(): void
    {
        $ambang = now()->subMinutes(self::AMBANG_MENIT);

        $perluNotifikasi = Device::whereNotNull('online')
            ->where('online', '<=', $ambang)
            ->get();

        foreach ($perluNotifikasi as $device) {
            $menit = (int) abs($device->online->diffInMinutes(now()));

            // Kolom "online" menyimpan waktu terakhir kontak, jadi dikosongkan
            // kembali agar tidak ikut terhitung pada eksekusi berikutnya.
            $device->forceFill(['online' => null])->save();

            Notifikasi::keAdmin(new DeviceOffline($device, $menit));

            $this->line("  offline: {$device->no_sn} ({$menit} menit)");
        }

        if ($perluNotifikasi->isNotEmpty()) {
            $this->info("{$perluNotifikasi->count()} device ditandai offline.");
        }
    }

    private function kirimPengingatJadwal(): void
    {
        $hariIni = self::namaHari();

        if ($hariIni === null) {
            $this->line('  hari ini bukan hari sekolah, jadwal dilewati.');

            return;
        }

        $kegiatan = Ekstrakulikuler::where('aktif', true)
            ->whereHas('siswa')
            ->where('hari', $hariIni)
            ->with(['siswa.user', 'guru.user'])
            ->get();

        if ($kegiatan->isEmpty()) {
            $this->line('  tidak ada kegiatan hari ini.');

            return;
        }

        foreach ($kegiatan as $item) {
            Notifikasi::kePeserta($item, new JadwalMendatang($item, 'peserta'));

            // Pengingat peran pembina dikirim ke guru pembinanya. Sebelumnya
            // dikirim ke admin, padahal nama sisinya sudah menyebut pembina.
            if ($item->guru?->user) {
                $item->guru->user->notify(new JadwalMendatang($item, 'pembina'));
            }

            $this->line("  pengingat: {$item->nama}");
        }

        $this->info(count($kegiatan).' kegiatan dikirim.');
    }
}