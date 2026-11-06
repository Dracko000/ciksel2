{{-- Angka pengajuan izin yang menunggu konfirmasi; ikut hilang setelah
     pengajuan diputuskan admin. --}}
<span wire:poll.60s="hitung" data-testid="izin-pending-badge" class="ml-auto shrink-0">
    @if ($jumlah > 0)
        <span class="flex h-5 min-w-5 items-center justify-center rounded-full bg-brand-600 px-1.5 text-[11px] font-semibold leading-none text-white">{{ $jumlah > 99 ? '99+' : $jumlah }}</span>
    @endif
</span>
