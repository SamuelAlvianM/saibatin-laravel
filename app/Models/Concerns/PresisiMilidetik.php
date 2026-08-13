<?php

namespace App\Models\Concerns;

/**
 * Seluruh kolom waktu portal ini bertipe `datetime(3)` — presisi milidetik,
 * warisan Prisma. Format tanggal bawaan Eloquent ('Y-m-d H:i:s') membuang
 * milidetiknya sehingga baris yang dibuat dalam detik yang sama kehilangan
 * urutan. Beberapa layar mengandalkan urutan itu (notifikasi, log aktivitas,
 * pesan tiket), jadi presisinya dipertahankan.
 *
 * Nilainya di-set lewat `initialize…` (dipanggil Eloquent di konstruktor),
 * BUKAN dengan mendeklarasikan ulang `protected $dateFormat`: PHP menolak trait
 * yang mendefinisikan properti sama dengan default berbeda dari kelas induknya
 * ("define the same property … considered incompatible").
 */
trait PresisiMilidetik
{
    public function initializePresisiMilidetik(): void
    {
        $this->dateFormat = 'Y-m-d H:i:s.v';
    }
}
