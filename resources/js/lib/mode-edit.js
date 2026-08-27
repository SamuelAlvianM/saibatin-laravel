/**
 * Penghubung Mode Edit antar-island.
 *
 * Halaman publik portal ini bukan satu aplikasi React melainkan sekumpulan
 * island terpisah (lihat `resources/js/publik.jsx`) — masing-masing punya root
 * sendiri, jadi tidak ada Context yang bisa dipakai bersama seperti
 * `InlineEditProvider` di portal Next.js. Modul inilah penggantinya: satu
 * keadaan bersama di tingkat modul, ditambah dua fungsi untuk memakainya.
 *
 * Yang memakai:
 *   • island `ModeEdit`  — pemilik keadaannya; menyalakan/mematikan & membuka dialog
 *   • island `ProfilTabs` — menampilkan tombol pensilnya sendiri di kepala kartu
 *     (blok profil berganti mengikuti tab yang aktif, jadi pensil melayang di
 *     atas seluruh island akan menyunting blok yang salah)
 *   • Blade mana pun — cukup menandai elemennya `data-blok="<kunci>"`
 */

let aktif = false;
const pendengar = new Set();

export function modeEditAktif() {
    return aktif;
}

/** Dipakai HANYA oleh island ModeEdit. */
export function setModeEdit(nilai) {
    aktif = nilai;
    pendengar.forEach((f) => f(nilai));
}

/** Berlangganan perubahan; balikannya menghentikan langganan. */
export function pantauModeEdit(fn) {
    pendengar.add(fn);
    return () => pendengar.delete(fn);
}

/** Minta dialog penyuntingan satu blok dibuka. */
export function bukaEditor(kunci) {
    window.dispatchEvent(new CustomEvent('konten:edit', { detail: kunci }));
}
