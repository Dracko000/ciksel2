<div class="space-y-6">
    <div>
        <h2 class="page-title">Manajemen Informasi Sekolah</h2>
        <p class="page-subtitle">
            Terbitkan pengumuman dan informasi resmi sekolah untuk seluruh pengguna.
        </p>
    </div>

    @if (session()->has('message'))
        <div class="card flex items-center gap-2 border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700"
             role="alert">
            <x-icon name="check" class="h-4 w-4 shrink-0" />
            <span>{{ session('message') }}</span>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="section-title">
                {{ $isEdit ? 'Edit Informasi' : 'Informasi Baru' }}
            </h3>

            @if ($isEdit)
                <span class="badge badge-warning">Mode edit</span>
            @endif
        </div>

        <div class="card-body">
            <form wire:submit.prevent="store" class="space-y-4">
                <div>
                    <label for="judul" class="label">Judul Pengumuman</label>
                    <input id="judul" wire:model="judul" type="text" class="input @error('judul') input-error @enderror"
                           placeholder="Contoh: Libur Nasional atau Ujian Semester">
                    @error('judul') <span class="help-error">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="tanggal_publikasi" class="label">Tanggal Publikasi</label>
                    <input id="tanggal_publikasi" wire:model="tanggal_publikasi" type="date"
                           class="input @error('tanggal_publikasi') input-error @enderror">
                    @error('tanggal_publikasi') <span class="help-error">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="konten" class="label">Isi Informasi</label>
                    <textarea id="konten" wire:model="konten" rows="5"
                              class="input @error('konten') input-error @enderror"
                              placeholder="Tuliskan detail pengumuman di sini..."></textarea>
                    @error('konten') <span class="help-error">{{ $message }}</span> @enderror
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 pt-4">
                    <button type="button" wire:click="resetFields" class="btn btn-secondary">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="check" class="h-4 w-4" />
                        {{ $isEdit ? 'Update Informasi' : 'Terbitkan Sekarang' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="section-title">Daftar Informasi</h3>
            <span class="badge badge-neutral">{{ $informasi->count() }} item</span>
        </div>

        <div class="card-body space-y-3">
            @forelse ($informasi as $item)
                <article wire:key="info-{{ $item->id }}" class="rounded-lg border border-slate-200 p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h4 class="text-sm font-semibold text-slate-800">{{ $item->judul }}</h4>
                            <p class="mt-0.5 text-xs text-slate-500">
                                {{ \Carbon\Carbon::parse($item->tanggal_publikasi)->format('d F Y') }}
                                <span class="text-slate-300">&middot;</span>
                                Oleh: {{ $item->author?->name ?? 'Tidak diketahui' }}
                            </p>
                        </div>

                        <div class="flex shrink-0 items-center gap-1">
                            <button type="button" wire:click="edit({{ $item->id }})" class="btn btn-ghost btn-sm">
                                <x-icon name="edit" class="h-4 w-4" />
                                Edit
                            </button>
                            <button type="button" wire:click="delete({{ $item->id }})"
                                    onclick="confirm('Hapus informasi ini?') || event.stopImmediatePropagation()"
                                    class="btn btn-sm text-rose-600 hover:bg-rose-50">
                                <x-icon name="trash" class="h-4 w-4" />
                                Hapus
                            </button>
                        </div>
                    </div>

                    <p class="mt-3 whitespace-pre-wrap text-sm leading-relaxed text-slate-600">
                        {{ Str::limit($item->konten, 200) }}
                    </p>
                </article>
            @empty
                <p class="py-8 text-center text-sm text-slate-500">
                    Belum ada informasi yang diterbitkan.
                </p>
            @endforelse
        </div>
    </div>
</div>