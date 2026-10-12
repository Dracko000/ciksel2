<div class="space-y-6">
    <div>
        <h1 class="page-title">Detail Absensi</h1>
        <p class="page-subtitle">Riwayat absen masuk dan pulang kamu.</p>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="section-title">Riwayat 30 Hari Terakhir</h2>
        </div>

        <div class="table-scroll">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Absen Masuk</th>
                        <th>Absen Pulang</th>
                        <th>Total Tap</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($absensi as $item)
                        <tr>
                            <td class="whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($item['date'])->translatedFormat('d M Y') }}
                            </td>
                            <td>
                                @if ($item['masuk'])
                                    <span class="badge badge-success">
                                        {{ \Carbon\Carbon::parse($item['masuk']->timestamp)->format('H:i:s') }}
                                    </span>
                                @else
                                    <span class="text-sm text-red-600">Belum absen</span>
                                @endif
                            </td>
                            <td>
                                @if ($item['pulang'])
                                    <span class="badge badge-brand">
                                        {{ \Carbon\Carbon::parse($item['pulang']->timestamp)->format('H:i:s') }}
                                    </span>
                                @else
                                    <span class="text-sm text-slate-500">-</span>
                                @endif
                            </td>
                            <td class="num">{{ $item['total'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-10 text-center text-sm text-slate-500">
                                Belum ada data absensi.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
