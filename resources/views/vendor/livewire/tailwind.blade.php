{{--
    Paginasi Livewire dengan merek ADMS.

    View bawaan Livewire memakai abu-abu/biru + varian dark mode, sehingga
    angka halaman aktif tidak terbaca sebagai merek aplikasi. Di sini angka
    aktif memakai brand-600 sesuai spek, tinggi tombol 32px (setara .btn-sm)
    dan radius dibatasi 8px.

    Override view bawaan diletakkan di resources/views/vendor/livewire/.

    PENTING untuk wire:click:
    - Setiap butir halaman WAJIB dibungkus <span wire:key="...">. Tanpa
      wire:key, Livewire tidak bisa mencocokkan tombol lama dan baru saat
      DOM di-morph setelah pindah halaman, sehingga klik page berikutnya
      tidak berefek.
    - Jangan pakai wire:loading.attr="disabled" pada tombol nomor. Kalau
      loading state tidak pernah selesai, tombolnya terkunci permanen dan
      tidak bisa diklik. View bawaan hanya memasangnya pada prev/next.
--}}
@php
    if (! isset($scrollTo)) {
        $scrollTo = 'body';
    }

    $scrollIntoViewJsSnippet = ($scrollTo !== false)
        ? <<<JS
            (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
            JS
        : '';
@endphp

@if ($paginator->hasPages())
    @php
        $btn = 'relative inline-flex h-8 items-center justify-center rounded-lg border border-slate-300 '
             . 'bg-white px-3 text-sm font-medium text-slate-700 transition-colors duration-150 '
             . 'hover:bg-slate-50 hover:text-slate-900 active:bg-slate-100';
        $btnActive = 'relative inline-flex h-8 min-w-8 items-center justify-center rounded-lg border '
                  . 'border-brand-600 bg-brand-600 px-3 text-sm font-semibold text-white';
    @endphp

    <nav role="navigation" aria-label="Navigasi halaman">
        <div class="flex flex-wrap items-center justify-end gap-1">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span class="{{ $btn }} cursor-default opacity-50" aria-disabled="true"
                      aria-label="Halaman sebelumnya">&laquo;</span>
            @else
                <button type="button"
                        wire:click="previousPage('{{ $paginator->getPageName() }}')"
                        x-on:click="{{ $scrollIntoViewJsSnippet }}"
                        wire:loading.attr="disabled"
                        class="{{ $btn }}"
                        aria-label="Halaman sebelumnya">&laquo;</button>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span aria-disabled="true">
                        <span class="relative inline-flex h-8 items-center justify-center px-1 text-sm font-medium text-slate-400">{{ $element }}</span>
                    </span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <span wire:key="paginator-{{ $paginator->getPageName() }}-page-{{ $page }}">
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page">
                                    <span class="{{ $btnActive }}">{{ $page }}</span>
                                </span>
                            @else
                                <button type="button"
                                        wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                        x-on:click="{{ $scrollIntoViewJsSnippet }}"
                                        class="{{ $btn }}"
                                        aria-label="Halaman {{ $page }}">{{ $page }}</button>
                            @endif
                        </span>
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <button type="button"
                        wire:click="nextPage('{{ $paginator->getPageName() }}')"
                        x-on:click="{{ $scrollIntoViewJsSnippet }}"
                        wire:loading.attr="disabled"
                        class="{{ $btn }}"
                        aria-label="Halaman berikutnya">&raquo;</button>
            @else
                <span class="{{ $btn }} cursor-default opacity-50" aria-disabled="true"
                      aria-label="Halaman berikutnya">&raquo;</span>
            @endif
        </div>
    </nav>
@endif