import Alpine from 'alpinejs';

// Alpine dipakai untuk interaksi ringan yang tidak butuh round-trip ke server:
// buka/tutup sidebar, dropdown notifikasi, dan konfirmasi hapus.
// CSS tetap dimuat terpisah lewat @vite di layout, bukan diimpor di sini
// supaya tidak terpasang dua kali.
window.Alpine = Alpine;

Alpine.start();