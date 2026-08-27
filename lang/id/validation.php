<?php

/*
|--------------------------------------------------------------------------
| Pesan validasi — Bahasa Indonesia
|--------------------------------------------------------------------------
|
| 🔴 KENAPA BERKAS INI ADA. Laravel 12 tidak lagi mengirim berkas bahasa apa
| pun di kerangka barunya, dan `lang/` memang tidak pernah dibuat di project
| ini. Akibatnya setiap aturan yang TIDAK punya pesan kustom dibalas dengan
| kunci terjemahannya sendiri: warga yang menekan "Daftar" dengan formulir
| kosong melihat tujuh baris berbunyi `validation.required` — tanpa nama kolom,
| tanpa petunjuk apa pun. Ditemukan lewat pengujian formulir 17 Agu 2026.
|
| Yang diterjemahkan hanya aturan yang benar-benar dipakai controller di sini
| (required, string, max, min, digits, email, integer, array, boolean) plus
| beberapa tetangga yang wajar muncul. Sisanya sengaja dibiarkan: `fallback_locale`
| tetap `en` dan `lang/en/validation.php` sudah diterbitkan, jadi aturan yang
| belum diterjemahkan tampil dalam bahasa Inggris yang terbaca — bukan kembali
| jadi kunci mentah.
|
| ⚠️ Pesan berkode (N-xx / L-xx) TIDAK dipindah ke sini. Kode itu dihafal
| petugas dinas saat melapor, dan tempatnya memang menempel pada aturannya di
| controller supaya terlihat saat aturannya disunting.
|
*/

return [

    'accepted' => ':attribute wajib disetujui.',
    'active_url' => ':attribute bukan URL yang sah.',
    'after' => ':attribute harus tanggal setelah :date.',
    'after_or_equal' => ':attribute harus tanggal setelah atau sama dengan :date.',
    'alpha' => ':attribute hanya boleh berisi huruf.',
    'alpha_dash' => ':attribute hanya boleh berisi huruf, angka, strip, dan garis bawah.',
    'alpha_num' => ':attribute hanya boleh berisi huruf dan angka.',
    'array' => ':attribute harus berupa daftar.',
    'before' => ':attribute harus tanggal sebelum :date.',
    'before_or_equal' => ':attribute harus tanggal sebelum atau sama dengan :date.',

    'between' => [
        'array' => ':attribute harus berisi antara :min dan :max item.',
        'file' => 'Ukuran :attribute harus antara :min dan :max kilobyte.',
        'numeric' => ':attribute harus bernilai antara :min dan :max.',
        'string' => ':attribute harus antara :min dan :max karakter.',
    ],

    'boolean' => ':attribute hanya boleh bernilai ya atau tidak.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'date' => ':attribute bukan tanggal yang sah.',
    'date_equals' => ':attribute harus tanggal yang sama dengan :date.',
    'date_format' => ':attribute tidak sesuai format :format.',
    'different' => ':attribute dan :other harus berbeda.',
    'digits' => ':attribute harus :digits digit.',
    'digits_between' => ':attribute harus antara :min dan :max digit.',
    'dimensions' => 'Ukuran gambar :attribute tidak sesuai.',
    'distinct' => ':attribute berisi nilai yang sama dua kali.',
    'email' => 'Format :attribute tidak valid.',
    'ends_with' => ':attribute harus diakhiri salah satu dari: :values.',
    'exists' => ':attribute yang dipilih tidak sah.',
    'file' => ':attribute harus berupa berkas.',
    'filled' => ':attribute wajib diisi.',

    'gt' => [
        'array' => ':attribute harus berisi lebih dari :value item.',
        'file' => 'Ukuran :attribute harus lebih dari :value kilobyte.',
        'numeric' => ':attribute harus lebih besar dari :value.',
        'string' => ':attribute harus lebih dari :value karakter.',
    ],
    'gte' => [
        'array' => ':attribute harus berisi :value item atau lebih.',
        'file' => 'Ukuran :attribute minimal :value kilobyte.',
        'numeric' => ':attribute minimal :value.',
        'string' => ':attribute minimal :value karakter.',
    ],

    'image' => ':attribute harus berupa gambar.',
    'in' => ':attribute yang dipilih tidak sah.',
    'integer' => ':attribute harus berupa angka bulat.',
    'ip' => ':attribute harus berupa alamat IP yang sah.',
    'ipv4' => ':attribute harus berupa alamat IPv4 yang sah.',
    'ipv6' => ':attribute harus berupa alamat IPv6 yang sah.',
    'json' => ':attribute harus berupa JSON yang sah.',

    'lt' => [
        'array' => ':attribute harus berisi kurang dari :value item.',
        'file' => 'Ukuran :attribute harus kurang dari :value kilobyte.',
        'numeric' => ':attribute harus lebih kecil dari :value.',
        'string' => ':attribute harus kurang dari :value karakter.',
    ],
    'lte' => [
        'array' => ':attribute tidak boleh lebih dari :value item.',
        'file' => 'Ukuran :attribute maksimal :value kilobyte.',
        'numeric' => ':attribute maksimal :value.',
        'string' => ':attribute maksimal :value karakter.',
    ],

    'max' => [
        'array' => ':attribute tidak boleh lebih dari :max item.',
        'file' => 'Ukuran :attribute tidak boleh lebih dari :max kilobyte.',
        'numeric' => ':attribute tidak boleh lebih dari :max.',
        'string' => ':attribute tidak boleh lebih dari :max karakter.',
    ],
    'min' => [
        'array' => ':attribute minimal berisi :min item.',
        'file' => 'Ukuran :attribute minimal :min kilobyte.',
        'numeric' => ':attribute minimal :min.',
        'string' => ':attribute minimal :min karakter.',
    ],

    'mimes' => ':attribute harus berupa berkas bertipe: :values.',
    'mimetypes' => ':attribute harus berupa berkas bertipe: :values.',
    'not_in' => ':attribute yang dipilih tidak sah.',
    'not_regex' => 'Format :attribute tidak sah.',
    'numeric' => ':attribute harus berupa angka.',
    'present' => ':attribute wajib ada.',
    'prohibited' => ':attribute tidak boleh diisi.',
    'regex' => 'Format :attribute tidak sah.',
    'required' => ':attribute wajib diisi.',
    'required_if' => ':attribute wajib diisi bila :other bernilai :value.',
    'required_unless' => ':attribute wajib diisi kecuali :other bernilai :values.',
    'required_with' => ':attribute wajib diisi bila ada :values.',
    'required_without' => ':attribute wajib diisi bila tidak ada :values.',
    'same' => ':attribute dan :other harus sama.',

    'size' => [
        'array' => ':attribute harus berisi :size item.',
        'file' => 'Ukuran :attribute harus :size kilobyte.',
        'numeric' => ':attribute harus bernilai :size.',
        'string' => ':attribute harus :size karakter.',
    ],

    'starts_with' => ':attribute harus diawali salah satu dari: :values.',
    'string' => ':attribute harus berupa teks.',
    'timezone' => ':attribute harus berupa zona waktu yang sah.',
    'unique' => ':attribute sudah terpakai.',
    'uploaded' => ':attribute gagal diunggah. Periksa ukuran berkasnya.',
    'url' => 'Format :attribute tidak sah.',
    'uuid' => ':attribute harus berupa UUID yang sah.',

    'custom' => [],

    /*
    |--------------------------------------------------------------------------
    | Nama kolom yang terbaca warga
    |--------------------------------------------------------------------------
    |
    | Tanpa daftar ini pesannya berbunyi "pass2 wajib diisi" — nama variabel,
    | bukan nama kolom yang dilihat warga di layar. Kuncinya adalah nama field
    | yang dikirim formulir, jadi ikut nama di `useForm`, bukan nama kolom DB.
    |
    */
    'attributes' => [
        // Pendaftaran & autentikasi
        'nama' => 'Nama lengkap',
        'nik' => 'NIK',
        'kk' => 'Nomor KK',
        'hp' => 'Nomor WhatsApp',
        'email' => 'Alamat email',
        'kecamatan' => 'Kecamatan domisili',
        'pass' => 'Password',
        'pass2' => 'Ulangi password',
        'pass1' => 'Password baru',
        'foto' => 'Foto wajah/selfie',
        'ktp' => 'Foto KTP',
        'user_id' => 'NIK/User ID',
        'password' => 'Password',
        'key' => 'Kode reset',
        'otpBukti' => 'Verifikasi OTP',

        // Ganti sandi di halaman profil
        'passwordLama' => 'Password lama',
        'passwordBaru' => 'Password baru',
        'konfirmasi' => 'Konfirmasi password',

        // Aspirasi warga
        'isi' => 'Isi pengaduan',
        'pesan' => 'Pesan',
        'subjek' => 'Subjek',
        'jawaban' => 'Penilaian unsur pelayanan',
        'jenisKel' => 'Jenis kelamin',
        'umur' => 'Umur',
        'pekerjaan' => 'Pekerjaan',

        // Konten dashboard
        'judul' => 'Judul',
        'kategori' => 'Kategori',
        'name' => 'Nama',
    ],

];
