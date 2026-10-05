{{--
    Toast notifikasi.

    Muncul di pojok kanan bawah dengan garis kiri 4px berwarna status dan
    tombol tutup, sesuai spek. Menghilang otomatis setelah beberapa detik
    (dihormati prefer reduced-motion) dan bisa ditutup manual.

    Dipakai di dalam view Livewire, bukan di layout, supaya toast langsung
    muncul setelah aksi tanpa perlu reload halaman.
--}}
@php
    $flashMessage = session('message');
    $flashType = session('message_type', 'success');
@endphp

@if ($flashMessage)
    <div class="pointer-events-none fixed inset-x-0 bottom-0 z-50 flex justify-center p-4 sm:justify-end"
         x-data="{ shown: true }"
         x-init="if (! window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                     setTimeout(() => shown = false, 5000);
                 }"
         x-show="shown"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         role="status"
         aria-live="polite">

        <div class="toast {{ $flashType === 'danger' ? 'toast-danger' : ($flashType === 'warning' ? 'toast-warning' : 'toast-success') }} w-full max-w-sm">
            <x-icon
                :name="$flashType === 'danger' ? 'warning' : ($flashType === 'warning' ? 'warning' : 'check')"
                class="mt-0.5 h-5 w-5 shrink-0 {{ $flashType === 'danger' ? 'text-bahaya-700' : ($flashType === 'warning' ? 'text-peringatan-700' : 'text-sukses-700') }}"
            />

            <p class="min-w-0 flex-1 text-sm font-medium text-slate-800">{{ $flashMessage }}</p>

            <button type="button"
                    x-on:click="shown = false"
                    class="-mr-1 -mt-1 shrink-0 rounded-md p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                    aria-label="Tutup notifikasi">
                <x-icon name="close" class="h-4 w-4" />
            </button>
        </div>
    </div>
@endif