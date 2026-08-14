<?php

/*
|--------------------------------------------------------------------------
| Halaman ketentuan: Kebijakan & Privasi + Syarat & Ketentuan
|--------------------------------------------------------------------------
|
| Port blok `info.kebijakan-privasi` & `info.syarat-ketentuan` dari
| `lib/static-content-registry.ts` SIDAKO. Teksnya sendiri berasal dari
| Laravel 9 asli (`fronts/kebijakanprivasis/index.blade.php`), jadi ini
| memulangkan naskah aslinya — bukan mengarang ketentuan baru.
|
| Nama daerah & aplikasi sudah disesuaikan ke SAIBATIN / Pesisir Barat.
|
| Bentuknya BERBEDA dari `config/info-halaman.php`: halaman ini bukan satu blok
| isi, melainkan beberapa BAGIAN bernomor yang masing-masing punya judul dan
| daftar poinnya sendiri. `urutan` menentukan bagian mana yang tampil dan
| dengan judul apa; isinya sendiri di `bawaan`, dan blok CMS menimpanya
| per-kunci lewat `App\Support\Konten`.
|
*/

return [

    'kebijakan-privasi' => [
        'kunci' => 'info.kebijakan-privasi',
        'title' => 'Kebijakan & Privasi',
        'description' => 'Ketentuan umum dan ketentuan penggunaan aplikasi SAIBATIN Disdukcapil Kabupaten Pesisir Barat.',
        'urutan' => [
            'umum' => 'Ketentuan Umum',
            'penggunaan' => 'Ketentuan Penggunaan Aplikasi',
        ],
    ],

    'syarat' => [
        'kunci' => 'info.syarat-ketentuan',
        'title' => 'Syarat & Ketentuan',
        'description' => 'Syarat dan ketentuan penggunaan portal SAIBATIN Disdukcapil Kabupaten Pesisir Barat.',
        'urutan' => [
            'umum' => 'Ketentuan Umum',
            'akun' => 'Pendaftaran & Akun Pengguna',
            'layanan' => 'Layanan Permohonan Online',
            'kewajiban' => 'Kewajiban & Tanggung Jawab Pemohon',
            'verifikasi' => 'Verifikasi, Pemrosesan & Jam Pelayanan',
            'dokumen' => 'Penerbitan & Pengambilan Dokumen',
            'larangan' => 'Larangan Penggunaan',
            'penutup' => 'Ketentuan Penutup',
        ],
    ],

    /*
    | Isi bawaan tiap blok. Selama blok belum pernah disunting petugas, inilah
    | yang tampil — halaman ketentuan tidak boleh pernah tampil kosong.
    */
    'bawaan' => [
        'info.kebijakan-privasi' => [
            'intro' => 'Terima kasih sudah menggunakan aplikasi SAIBATIN — Dinas Kependudukan dan Pencatatan Sipil Kabupaten Pesisir Barat.',
            'umum' => [
                'Aplikasi ini merupakan peralihan dari layanan offline (di kantor) Dinas Kependudukan dan Pencatatan Sipil Kabupaten Pesisir Barat.',
                'Pengunduhan dan/atau penggunaan aplikasi ini bebas biaya. Koneksi ke jaringan internet diperlukan untuk dapat menggunakan layanan ini; segala biaya yang timbul atas koneksi perangkat pemohon dengan jaringan internet sepenuhnya ditanggung oleh pemohon.',
                'Aplikasi ini dapat digunakan oleh pemohon dengan terlebih dahulu melakukan pendaftaran yang disertai pemberian informasi data pribadi pemohon sebagaimana diminta dalam aplikasi. Informasi data pribadi yang diberikan hanya akan digunakan untuk pemberian layanan dan tujuan lain yang dimuat dalam kebijakan privasi. Informasi tambahan wajib pemohon berikan untuk dapat menggunakan layanan tertentu dalam aplikasi.',
                'Aplikasi ini bertujuan memberikan informasi secara umum terkait produk dan layanan yang kami sediakan. Kami senantiasa berupaya menjaga kebenaran dan kekinian informasi tersebut, namun tidak membuat pernyataan dan jaminan apa pun, baik tersurat maupun tersirat, mengenai kelengkapan, akurasi, keandalan, kesesuaian, keamanan, kecepatan, maupun ketersediaan fitur, informasi, produk, layanan, gambar, atau grafis dalam aplikasi. Gambar, grafis, dan/atau foto dalam aplikasi mungkin tunduk pada hak kekayaan intelektual pihak ketiga.',
                'Penggunaan beberapa layanan tertentu dalam aplikasi mensyaratkan Anda memberikan akses pada kamera dan media penyimpanan; ini diperlukan untuk mempermudah kami memverifikasi kebenaran data yang Anda berikan.',
                'Aplikasi ini tidak terhubung ke database kependudukan; aplikasi ini hanya merupakan alat bantu dalam pencatatan proses registrasi.',
                'Kami memiliki kebijakan sendiri dan menyeluruh untuk menerima, menunda, atau menolak permintaan Anda atas layanan.',
            ],
            'penggunaan' => [
                'Anda menyatakan dan menjamin bahwa Anda adalah individu yang secara hukum berhak dan cakap berdasarkan hukum Negara Republik Indonesia untuk meminta layanan dari Dinas Kependudukan dan Pencatatan Sipil Kabupaten Pesisir Barat serta menggunakan aplikasi ini. Apabila ketentuan tersebut tidak terpenuhi, kami berhak membatalkan setiap layanan yang Anda buat.',
                'Jika Anda mendaftar untuk dan atas nama suatu institusi, Anda menyatakan dan menjamin bahwa Anda berwenang bertindak untuk dan atas nama institusi tersebut dengan menunjukkan surat penunjukan.',
                'Kami mengumpulkan dan memproses data pribadi Anda seperti nama, alamat, nomor kartu identitas, nomor telepon, alamat surel, dan tanggal lahir saat Anda mendaftar dan menggunakan aplikasi. Anda wajib memberikan informasi yang akurat dan lengkap serta memperbaruinya dari waktu ke waktu, dan setuju memberikan bukti identitas yang secara wajar kami minta.',
                'Dalam hal terjadi penggunaan kata sandi akun Anda dengan cara apa pun yang bukan karena kesalahan kami dan mengakibatkan penggunaan tanpa kewenangan, permintaan yang dilakukan melalui aplikasi tetap dianggap permintaan yang sah, kecuali Anda memberitahu kami sebelum layanan diberikan.',
                'Anda wajib melaporkan kepada kami bila kehilangan kendali atas akun Anda. Anda bertanggung jawab atas setiap penggunaan akun Anda meskipun akun tersebut disalahgunakan pihak lain.',
                'Anda dapat mengunggah informasi, foto, penilaian, dan komentar pada fitur dalam aplikasi. Anda dilarang mengunggah konten bermuatan SARA, pornografi, atau pelanggaran hak kekayaan intelektual. Kami berhak menghapus atau memblokir unggahan maupun akun yang melanggar ketentuan penggunaan.',
                'Anda tidak diperkenankan membahayakan, menyalahgunakan, mengubah, atau memodifikasi aplikasi dengan cara apa pun. Kami berhak menghentikan penggunaan akun Anda bila aplikasi digunakan tanpa mematuhi ketentuan penggunaan.',
                'Anda hanya diizinkan menggunakan aplikasi ini untuk layanan yang disediakan dan keperluan lain sesuai peraturan perundang-undangan. Anda dilarang menggunakan aplikasi untuk penipuan dalam bentuk apa pun, membuat ketidaknyamanan terhadap pihak lain, menyalahgunakan informasi yang diperoleh dari layanan, serta melecehkan atau mengancam pihak penyedia layanan.',
                'Anda memahami dan setuju bahwa penggunaan aplikasi tunduk pula pada kebijakan privasi kami yang dapat diubah dari waktu ke waktu; dengan menggunakan aplikasi, Anda dianggap memberikan persetujuan atas kebijakan privasi tersebut.',
                'Anda dilarang menggunakan layanan dalam aplikasi untuk hal-hal yang dilarang oleh hukum dan peraturan perundang-undangan yang berlaku.',
            ],
        ],

        'info.syarat-ketentuan' => [
            'intro' => 'Selamat datang di SAIBATIN — portal layanan administrasi kependudukan dan pencatatan sipil Dinas Kependudukan dan Pencatatan Sipil Kabupaten Pesisir Barat. Dengan mendaftar dan/atau menggunakan layanan pada portal ini, Anda dianggap telah membaca, memahami, dan menyetujui seluruh Syarat & Ketentuan berikut.',
            'pembaruan' => 'Terakhir diperbarui: Juli 2026',
            'umum' => [
                'SAIBATIN adalah portal layanan administrasi kependudukan berbasis daring milik Dinas Kependudukan dan Pencatatan Sipil Kabupaten Pesisir Barat, sebagai peralihan dari layanan tatap muka di kantor.',
                'Seluruh layanan pada portal ini tidak dipungut biaya (gratis). Biaya koneksi internet untuk mengakses layanan sepenuhnya menjadi tanggung jawab pemohon.',
                'Portal ini merupakan alat bantu pencatatan proses permohonan; penerbitan dokumen tetap tunduk pada verifikasi dan ketentuan Dinas Kependudukan dan Pencatatan Sipil Kabupaten Pesisir Barat.',
                'Beberapa layanan mensyaratkan akses kamera dan media penyimpanan perangkat untuk pengambilan foto serta pengunggahan berkas verifikasi.',
                'Dinas berupaya menjaga kebenaran dan kekinian informasi pada portal, namun tidak menjamin secara mutlak kelengkapan, akurasi, keandalan, keamanan, maupun ketersediaan seluruh fitur setiap saat.',
                'Dengan menggunakan portal ini, Anda juga menyetujui Kebijakan Privasi yang berlaku dan dapat diperbarui sewaktu-waktu.',
            ],
            'akun' => [
                'Untuk mengajukan permohonan, Anda wajib memiliki akun dengan mendaftar dan mengisi data pribadi (nama, NIK, alamat, nomor telepon, surel, dan data lain) sesuai dokumen resmi.',
                'Saat pendaftaran, Anda wajib mengambil swafoto (foto wajah) secara langsung untuk dicocokkan dengan KTP-elektronik sebagai bagian verifikasi identitas; foto tersebut menjadi foto profil akun Anda.',
                'Anda menjamin bahwa seluruh data dan dokumen yang diberikan benar, akurat, terbaru, dan menjadi hak Anda, serta bersedia menunjukkan bukti identitas apabila diminta.',
                'Satu akun digunakan oleh satu orang. Anda bertanggung jawab menjaga kerahasiaan kata sandi dan seluruh aktivitas yang terjadi pada akun Anda.',
                'Setiap permohonan yang diajukan melalui akun Anda dianggap sah dan berasal dari Anda, kecuali Anda melaporkan kehilangan kendali atas akun sebelum layanan diproses.',
                'Anda wajib segera memberitahu Dinas apabila mengetahui adanya penggunaan akun tanpa izin.',
            ],
            'layanan' => [
                'Portal menyediakan permohonan dokumen kependudukan dan pencatatan sipil, antara lain: akta kelahiran, akta kematian, akta perkawinan, akta perceraian, Kartu Keluarga (KK), KTP-elektronik, Kartu Identitas Anak (KIA), surat pindah/datang (SKPWNI), serta layanan kependudukan lain yang tersedia.',
                'Setiap jenis permohonan mewajibkan pemohon melengkapi persyaratan dan mengunggah berkas pendukung yang sah, jelas, dan terbaca sesuai ketentuan masing-masing layanan.',
                'Data pada formulir permohonan harus sesuai dengan dokumen resmi; ketidaksesuaian dapat menyebabkan permohonan ditolak atau dikembalikan untuk diperbaiki.',
                'Satu permohonan diajukan untuk satu peristiwa atau satu subjek sesuai jenis layanan yang dipilih.',
                'Pengajuan permohonan hanya dapat dilakukan pada jam pelayanan aktif yang ditetapkan Dinas; di luar jam tersebut formulir permohonan dinonaktifkan sementara.',
            ],
            'kewajiban' => [
                'Memberikan data dan dokumen yang benar; permohonan dengan data atau dokumen palsu maupun menyesatkan dapat dibatalkan sepihak dan/atau diproses sesuai hukum yang berlaku.',
                'Mengunggah berkas milik sendiri atau yang Anda berhak menggunakannya, tanpa melanggar hak pihak lain.',
                'Menjaga kerahasiaan akun dan tidak mengalihkan akun kepada pihak lain.',
                'Tidak menyalahgunakan layanan untuk tujuan penipuan, komersial tanpa izin, atau perbuatan melawan hukum.',
                'Bertanggung jawab penuh atas seluruh permohonan dan unggahan yang dilakukan melalui akun Anda.',
            ],
            'verifikasi' => [
                'Dinas berhak memverifikasi ulang seluruh data dan berkas pemohon sebelum dokumen diterbitkan.',
                'Dinas berhak menerima, menunda, meminta perbaikan, atau menolak permohonan disertai alasan, sesuai ketentuan dan standar pelayanan yang berlaku.',
                'Waktu pemrosesan mengikuti Standar Operasional Prosedur (SOP) dan Standar Pelayanan masing-masing layanan; estimasi waktu bukan jaminan mutlak dan dipengaruhi kelengkapan berkas serta antrean.',
                'Permohonan hanya diproses atas berkas yang lengkap dan memenuhi syarat.',
                'Pengajuan hanya dilayani pada hari dan jam pelayanan aktif; permohonan di luar jam aktif dapat diajukan kembali pada jam pelayanan berikutnya.',
            ],
            'dokumen' => [
                'Dokumen yang telah selesai dapat berupa dokumen digital bertanda tangan elektronik (TTE) yang sah sesuai peraturan perundang-undangan.',
                'Pemohon memperoleh notifikasi status permohonan (diproses, perlu perbaikan, disetujui, atau ditolak) melalui portal.',
                'Dokumen digital dapat diunduh melalui akun pemohon; pengambilan dokumen fisik (bila ada) mengikuti ketentuan Dinas.',
                'Kehilangan atau kerusakan dokumen dapat diajukan penerbitan ulang sesuai prosedur yang berlaku.',
            ],
            'larangan' => [
                'Mengunggah konten bermuatan SARA, pornografi, kebencian, atau yang melanggar hak kekayaan intelektual pihak lain.',
                'Menggunakan portal untuk penipuan, pemalsuan dokumen atau identitas, maupun tindakan melawan hukum lainnya.',
                'Merusak, menyalahgunakan, mengubah, atau mengganggu keamanan dan kinerja sistem portal.',
                'Melecehkan, mengancam, atau membuat ketidaknyamanan terhadap petugas maupun pengguna lain.',
                'Dinas berhak menghapus unggahan, menonaktifkan, atau memblokir akun yang melanggar ketentuan ini.',
            ],
            'penutup' => [
                'Dinas dapat mengubah, memperbarui, atau menyesuaikan Syarat & Ketentuan ini sewaktu-waktu; perubahan berlaku sejak dipublikasikan pada portal.',
                'Syarat & Ketentuan ini merupakan satu kesatuan dengan Kebijakan Privasi yang berlaku.',
                'Segala hal yang timbul dari penggunaan portal tunduk pada hukum Negara Republik Indonesia.',
                'Untuk pertanyaan atau bantuan, silakan hubungi Dinas Kependudukan dan Pencatatan Sipil Kabupaten Pesisir Barat melalui kanal resmi yang tersedia pada portal.',
            ],
            'image' => '',
        ],

    ],

];
