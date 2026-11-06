@props([
    'taps' => [],
    'judul' => 'RFID Belum Terdaftar',
    'keterangan' => 'Tap dari mesin yang RFID/ID-nya belum cocok dengan data siswa maupun guru. Buka menu RFID Belum Terdaftar untuk menghubungkan RFID berikut ke pemiliknya.',
])

{{-- Tap asing tidak masuk donut/status karena belum punya identitas. Bagian ini
     hanya menampilkan RFID apa adanya dari mesin supaya bisa dicocokkan. --}}
<div {{ $attributes->merge(['class' => 'table-shell']) }}>
    <div class="card-header">
        <div>
            <h3 class="section-title">{{ $judul }}</h3>
            <p class="mt-1 max-w-2xl text-xs text-slate-500">{{ $keterangan }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="badge badge-neutral">{{ count($taps) }} RFID</span>

            @if (count($taps) > 0)
                <a href="{{ route('admin.rfid') }}" class="btn btn-sm btn-secondary">
                    <x-icon name="user" class="h-3.5 w-3.5" />
                    Hubungkan
                </a>
            @endif
        </div>
    </div>

    <div class="table-scroll">
        <table class="table">
            <thead>
                <tr>
                    <th class="w-14">No</th>
                    <th>RFID (dari mesin)</th>
                    <th>Mesin</th>
                    <th>Jam Masuk</th>
                    <th>Jam Pulang</th>
                    <th>Jumlah Tap</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($taps as $tap)
                    <tr wire:key="belum-terdaftar-{{ $tap['employee_id'] }}">
                        <td class="num text-xs text-slate-400">{{ $loop->iteration }}</td>
                        <td class="num text-xs font-semibold text-slate-800">{{ $tap['employee_id'] }}</td>
                        <td class="text-xs text-slate-600">
                            {{ ! empty($tap['mesin']) ? implode(', ', $tap['mesin']) : '—' }}
                        </td>
                        <td class="num text-xs font-medium text-slate-700">{{ $tap['masuk'] ?? '—' }}</td>
                        <td class="num text-xs font-medium text-slate-700">{{ $tap['pulang'] ?? '—' }}</td>
                        <td class="num text-xs text-slate-500">{{ $tap['jumlahTap'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-sm text-slate-500">
                            Semua tap pada tanggal ini sudah cocok dengan data siswa/guru.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
