<?php

/*
|--------------------------------------------------------------------------
| Halaman layanan PPID dua-seksi — Formulir & Register
|--------------------------------------------------------------------------
|
| Port `components/ppid/ppid-layanan-halaman.tsx` SIDAKO
| (`app/ppid/formulir-ppid` & `app/ppid/register-ppid`).
|
| Beda dari halaman `config/info-halaman.php`: satu alamat memuat DUA seksi
| yang masing-masing punya isi, gambar, dan kategori dokumennya sendiri —
| formulir permohonan vs formulir keberatan, register permintaan vs register
| keberatan. Memaksakannya jadi dua halaman informasi terpisah akan memecah
| sepasang berkas yang di dinas memang selalu dibaca bersama.
|
| Tiap seksi bisa ditimpa blok CMS `info.ppid.<slug>` — jadi petugas menyunting
| teksnya lewat Konten Halaman persis seperti halaman informasi biasa.
|
*/

return [

    'formulir-ppid' => [
        'judul' => 'Formulir PPID',
        'deskripsi' => 'Unduh formulir permohonan informasi publik dan formulir pernyataan keberatan. Ikuti alur pada infografis, lengkapi formulir, lalu ajukan sesuai ketentuan layanan PPID.',
        'duaKolom' => false,
        'seksi' => [
            [
                'slug' => 'formulir-permohonan',
                'dokumen' => 'FORMULIR_PERMOHONAN',
                'isi' => [
                    'title' => 'Formulir Permohonan Informasi',
                    'description' => 'Alur dan formulir untuk mengajukan permohonan informasi publik kepada PPID Disdukcapil Pesisir Barat.',
                    'body' => [
                        'Pemohon mengajukan permohonan informasi kepada PPID, baik secara langsung maupun melalui surat/email/telepon. PPID mencatat, memverifikasi, dan menyampaikan informasi paling lama 10 hari kerja sesuai ketentuan. Unduh formulir permohonan pada tabel berkas di bawah dan lampirkan fotokopi KTP.',
                    ],
                ],
            ],
            [
                'slug' => 'formulir-keberatan',
                'dokumen' => 'FORMULIR_KEBERATAN',
                'isi' => [
                    'title' => 'Formulir Pernyataan Keberatan Atas Permohonan Informasi',
                    'description' => 'Alur dan formulir untuk mengajukan keberatan bila permohonan informasi tidak dipenuhi atau tidak sesuai.',
                    'body' => [
                        'Jika pemohon informasi tidak puas dengan jawaban/keputusan PPID, pemohon dapat mengajukan keberatan kepada Atasan PPID paling lambat 30 hari kerja sejak ditemukannya alasan keberatan. Unduh formulir pernyataan keberatan pada tabel berkas di bawah.',
                    ],
                ],
            ],
        ],
    ],

    'register-ppid' => [
        'judul' => 'Register PPID',
        'deskripsi' => 'Buku register pencatatan permohonan informasi publik dan register keberatan yang masuk ke PPID Disdukcapil Kabupaten Pesisir Barat.',
        'duaKolom' => true,
        'seksi' => [
            [
                'slug' => 'register-permintaan',
                'dokumen' => 'REGISTER_PERMINTAAN',
                'isi' => [
                    'title' => 'Register Permintaan Informasi Publik',
                    'description' => 'Daftar/rekapitulasi permohonan informasi publik yang diterima dan ditindaklanjuti PPID.',
                    'body' => [
                        'Register ini mencatat setiap permohonan informasi publik: identitas pemohon, informasi yang diminta, tanggal permohonan, serta status tindak lanjutnya. Dokumen register dapat diunduh pada tabel berkas di bawah.',
                    ],
                ],
            ],
            [
                'slug' => 'register-keberatan',
                'dokumen' => 'REGISTER_KEBERATAN',
                'isi' => [
                    'title' => 'Register Keberatan',
                    'description' => 'Daftar/rekapitulasi keberatan atas permohonan informasi publik yang diajukan kepada Atasan PPID.',
                    'body' => [
                        'Register ini mencatat setiap pengajuan keberatan: identitas pemohon, alasan keberatan, tanggal pengajuan, serta tanggapan dan penyelesaiannya. Dokumen register dapat diunduh pada tabel berkas di bawah.',
                    ],
                ],
            ],
        ],
    ],

];
