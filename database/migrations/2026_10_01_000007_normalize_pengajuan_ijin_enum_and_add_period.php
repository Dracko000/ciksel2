<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nilai alpha dihapus dari enum karena alpa dihitung dari ketiadaan tap,
 * bukan dicatat sebagai pengajuan. Data alpha yang sudah terlanjur ada
 * dipetakan ke izin agar tidak hilang, lalu enum dipersempit.
 * Nilai ijin juga diseragamkan menjadi izin mengikuti penamaan yang dipakai
 * halaman portal.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pengajuan_ijin') && Schema::hasColumn('pengajuan_ijin', 'jenis')) {
            $this->renameExistingEnumValues();
            $this->redefineEnum();
        }

        $this->addPeriodColumns('pengajuan_ijin', ['tanggal_selesai', 'status']);
        $this->addPeriodColumns('jadwal_pelajaran', ['jam_selesai']);
    }

    public function down(): void
    {
        $this->dropPeriodColumns('jadwal_pelajaran');
        $this->dropPeriodColumns('pengajuan_ijin');

        if (Schema::hasTable('pengajuan_ijin') && Schema::hasColumn('pengajuan_ijin', 'jenis')) {
            DB::table('pengajuan_ijin')
                ->where('jenis', 'izin')
                ->update(['jenis' => 'ijin']);

            $this->redefineEnum(true);
        }
    }

    /**
     * tahun_ajaran dan semester membatasi rekap absensi serta perhitungan
     * keterlambatan ke dalam satu periode akademik. Kolom dibuat nullable
     * supaya data lama tidak perlu diisi ulang, dan memakai default periode
     * berjalan agar baris baru tidak cranks.
     *
     * @param  array<int, string>  $afterColumns
     */
    private function addPeriodColumns(string $tableName, array $afterColumns): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }

        $anchor = $this->anchorColumn($tableName, $afterColumns);

        Schema::table($tableName, function ($table) use ($tableName, $anchor) {
            if (! Schema::hasColumn($tableName, 'tahun_ajaran')) {
                $table->string('tahun_ajaran', 20)->nullable()->after($anchor);
            }

            if (! Schema::hasColumn($tableName, 'semester')) {
                $table->string('semester', 20)->nullable()->after('tahun_ajaran');
            }
        });

        $this->backfillPeriod($tableName);
    }

    /**
     * @param  array<int, string>  $afterColumns
     */
    private function dropPeriodColumns(string $tableName): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }

        $columns = array_values(array_filter(
            ['semester', 'tahun_ajaran'],
            fn (string $column) => Schema::hasColumn($tableName, $column)
        ));

        if ($columns === []) {
            return;
        }

        Schema::table($tableName, function ($table) use ($columns) {
            $table->dropColumn($columns);
        });
    }

    private function backfillPeriod(string $tableName): void
    {
        $period = config('adms.academic_period');

        if (! is_string($period) || $period === '') {
            return;
        }

        [$tahunAjaran, $semester] = array_pad(explode('|', $period), 2, null);

        DB::table($tableName)
            ->whereNull('tahun_ajaran')
            ->update([
                'tahun_ajaran' => $tahunAjaran,
                'semester' => $semester,
            ]);
    }

    /**
     * @param  array<int, string>  $afterColumns
     */
    private function anchorColumn(string $tableName, array $afterColumns): ?string
    {
        foreach ($afterColumns as $column) {
            if (Schema::hasColumn($tableName, $column)) {
                return $column;
            }
        }

        return null;
    }

    /**
     * MySQL tidak bisa mengubah nilai enum dengan ADD/MODIFY, jadi nilai lama
     * ditulis lebih dulu lalu diganti. Tabel pengajuan kosong pada instalasi
     * baru, langkah ini dilewati otomatis.
     */
    private function renameExistingEnumValues(): void
    {
        DB::table('pengajuan_ijin')
            ->where('jenis', 'alpha')
            ->update(['jenis' => 'izin']);

        DB::table('pengajuan_ijin')
            ->where('jenis', 'ijin')
            ->update(['jenis' => 'izin']);
    }

    private function redefineEnum(bool $restoreAlpha = false): void
    {
        $definition = $restoreAlpha
            ? "enum('sakit','izin','alpha')"
            : "enum('sakit','izin')";

        DB::statement(
            'ALTER TABLE `pengajuan_ijin` MODIFY `jenis` '.$definition.' NOT NULL'
        );
    }
};
