{{--
    Modal konfirmasi hapus.

    Hapus tidak dieksekusi dari satu klik. Admin harus mengetik ulang nama
    entitas (atau kata "HAPUS" untuk penghapusan massal) supaya salah pilih
    pada daftar hundreds baris tidak berakibat fatal dan tidak bisa
    dibatalkan.

    Parent.Livewire yang Memakai Component Ini
    ------------------------------------------
    - property boolean pembuka modal, mis. $deleteMode
    - property teks ketikan, mis. $deleteConfirmText
    - method pembuka/pembatal, mis. cancelDelete
    - method eksekusi, mis. confirmDelete
--}}
@props([
    'open' => false,
    'title' => 'Hapus data?',
    'targetName' => '',
    'impact' => null,
    'inputName' => 'deleteConfirmText',
    'errorKey' => 'deleteConfirmText',
    'confirmMethod' => 'confirmDelete',
    'cancelMethod' => 'cancelDelete',
    'confirmLabel' => 'Hapus permanen',
    'confirmValue' => '',
])

@if ($open)
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="konfirmasi-hapus-judul"
        x-data
        x-init="$nextTick(() => $refs.konfirmasi.focus())"
        x-on:keydown.escape.window="{{ $cancelMethod }}()"
    >
        <div class="w-full max-w-md rounded-lg border border-slate-200 bg-white p-6 shadow-xl">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-bahaya-50 text-bahaya-700">
                    <x-icon name="warning" class="h-5 w-5" />
                </span>

                <div class="min-w-0">
                    <h3 id="konfirmasi-hapus-judul" class="text-base font-semibold text-slate-900">
                        {{ $title }}
                    </h3>

                    <p class="mt-1 text-sm text-slate-600">
                        {{ $impact ?? 'Tindakan ini tidak dapat dibatalkan.' }}
                    </p>
                </div>
            </div>

            <div class="mt-5">
                <label for="ketik-nama-entitas" class="label">
                    Ketik <span class="font-semibold text-slate-800">{{ $targetName }}</span> untuk melanjutkan
                </label>

                <input
                    id="ketik-nama-entitas"
                    x-ref="konfirmasi"
                    wire:model="{{ $inputName }}"
                    type="text"
                    autocomplete="off"
                    placeholder="{{ $targetName }}"
                    class="input @error($errorKey) input-error @enderror"
                >

                @error($errorKey)
                    <p class="help-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button type="button" wire:click="{{ $cancelMethod }}" class="btn btn-secondary">
                    Batal
                </button>

                <button
                    type="button"
                    wire:click="{{ $confirmMethod }}"
                    class="btn btn-danger"
                    @disabled(trim((string) $confirmValue) === '')
                >
                    <x-icon name="trash" class="h-4 w-4" />
                    {{ $confirmLabel }}
                </button>
            </div>
        </div>
    </div>
@endif