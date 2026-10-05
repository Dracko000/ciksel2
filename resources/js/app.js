// CATATAN: jangan meng-import Alpine di sini.
//
// Livewire v3 sudah membundel Alpine (sekitar 3.15.x) di dalam livewire.js
// dan menjalankannya sendiri. Kalau file ini juga meng-import paket
// 'alpinejs' lalu memanggil Alpine.start(), akan ada DUA instance Alpine
// berjalan di satu DOM.
//
// Urutan eksekusi skrip di layout:
//   1. livewire.js (script klasik) -> Alpine bawaan Livewire jalan,
//      directive wire:* terpasang.
//   2. app.js (type="module", otomatis deferred) -> Alpine dari node_modules
//      jalan belakangan dan menginisialisasi ulang seluruh DOM, sehingga
//      penangan wire:click yang sudah terpasang dibuang.
//
// Akibatnya setiap wire:click mati: paginasi tidak bisa diklik, tombol
// action tidak merespons, checkbox tidak tercentang.
//
// Semua kebutuhan x-data / x-show / x-on pada blade ditangani Alpine bawaan
// Livewire, jadi file ini tidak perlu melakukan apa-apa.

// Titik masuk Vite tetap dipertahankan (layout memanggil @vite), tetapi
// tidak ada bundle Alpine tambahan yang perlu dimuat.
export {};