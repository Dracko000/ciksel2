<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-800">Konfirmasi Izin / Sakit</h1>
            <p class="mt-1 text-sm text-slate-500">Pengajuan izin dan sakit siswa yang menunggu keputusan Anda.</p>
        </div>

        <a href="{{ route('admin.konfirmasi') }}"
            class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
            Muat ulang
        </a>
    </div>

    @if (session()->has('message'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="alert">
            {{ session('message') }}
        </div>
    @endif

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-full border-collapse text-left">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Siswa</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Jenis</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Keterangan</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($pengajuan as $item)
                        <tr wire:key="ijin-{{ $item->id }}" class="bg-white transition hover:bg-slate-50">
                            <td class="px-4 py-3 text-sm font-medium text-slate-800">{{ $item->siswa->nama }}</td>
                            <td class="px-4 py-3">
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                    'bg-red-100 text-red-700' => $item->jenis === 'sakit',
                                    'bg-amber-100 text-amber-800' => $item->jenis !== 'sakit',
                                ])>{{ ucfirst($item->jenis) }}</span>
                            </td>
                            <td class="max-w-xs px-4 py-3 text-sm text-slate-600">{{ $item->keterangan }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600">
                                {{ \Carbon\Carbon::parse($item->tanggal_mulai)->format('d/m/Y') }}
                                @if ($item->tanggal_selesai && $item->tanggal_selesai !== $item->tanggal_mulai)
                                    &ndash; {{ \Carbon\Carbon::parse($item->tanggal_selesai)->format('d/m/Y') }}
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($item->status === 'pending')
                                    <span class="inline-flex items-center gap-1.5 text-sm font-medium text-amber-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> Menunggu
                                    </span>
                                @elseif ($item->status === 'disetujui')
                                    <span class="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Disetujui
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-sm font-medium text-rose-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span> Ditolak
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($item->status === 'pending')
                                    <div class="flex gap-2">
                                        <button type="button" wire:click="approve({{ $item->id }})"
                                            wire:loading.attr="disabled" wire:target="approve({{ $item->id }})"
                                            class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:opacity-60">
                                            Setuju
                                        </button>
                                        <button type="button" wire:click="reject({{ $item->id }})"
                                            wire:loading.attr="disabled" wire:target="reject({{ $item->id }})"
                                            class="inline-flex items-center rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-rose-700 disabled:opacity-60">
                                            Tolak
                                        </button>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">&mdash;</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">
                                Belum ada pengajuan izin.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($pengajuan->hasPages())
            <div class="border-t border-slate-200 px-4 py-3">
                {{ $pengajuan->links() }}
            </div>
        @endif
    </div>
</div>