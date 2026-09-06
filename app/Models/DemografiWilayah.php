<?php

namespace App\Models;

use App\Models\Concerns\PresisiMilidetik;
use Illuminate\Database\Eloquent\Model;

/**
 * Agregat demografi per wilayah, hasil impor Excel Dukcapil (SIAK).
 *
 * `kategori` = jenis-kelamin | agama | gol-darah | pekerjaan | kk | pendidikan |
 *              status-kawin | wajib-ktp
 * `level`    = 4 kecamatan · 5 pekon/kelurahan (hierarki lewat `parent_kode`)
 * `data`     = kolom nilai per kategori, mis. { L, P, JML }
 * `tahun` + `semester` = periode DKB baris ini (semester 1 atau 2)
 *
 * 🔴 Setiap kueri WAJIB menyaring periode. Tanpa itu angka dua semester ikut
 * terjumlah sekaligus dan hasilnya dua kali lipat — persis kesalahan yang
 * kunci unik lama `(kategori, kode)` dulu membuatnya mustahil terjadi, dan
 * kini menjadi mungkin justru karena periodenya bisa berdampingan.
 *
 * Baris di-upsert setiap impor, jadi tabel ini sengaja tak punya created_at.
 */
class DemografiWilayah extends Model
{
    use PresisiMilidetik;

    /** Kabupaten/kota — baris ringkasan; TIDAK boleh ikut dijumlah
     *  bersama kecamatan di bawahnya. */
    public const LEVEL_KABUPATEN = 3;

    public const LEVEL_KECAMATAN = 4;
    public const LEVEL_KELURAHAN = 5;

    protected $table = 'm_demografi_wilayah';

    /** Hanya updated_at yang bermakna. */
    public const CREATED_AT = null;

    protected $fillable = ['kategori', 'tahun', 'semester', 'kode', 'wilayah', 'level', 'parent_kode', 'data'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'level' => 'integer',
            'tahun' => 'integer',
            'semester' => 'integer',
        ];
    }

    /** Saring satu periode. Selalu dipakai — lihat catatan di atas. */
    public function scopePeriode($q, int $tahun, int $semester)
    {
        return $q->where('tahun', $tahun)->where('semester', $semester);
    }

    /**
     * Periode yang benar-benar punya data, terbaru dulu.
     *
     * @return array<int, array{tahun:int, semester:int, baris:int}>
     */
    public static function periodeTersedia(?string $kategori = null): array
    {
        return static::query()
            ->when($kategori, fn ($w) => $w->where('kategori', $kategori))
            ->selectRaw('tahun, semester, COUNT(*) as baris')
            ->groupBy('tahun', 'semester')
            ->orderByDesc('tahun')
            ->orderByDesc('semester')
            ->get()
            ->map(fn ($r) => [
                'tahun' => (int) $r->tahun,
                'semester' => (int) $r->semester,
                'baris' => (int) $r->baris,
            ])
            ->all();
    }

    /**
     * Periode TERBARU yang ada datanya, atau null bila tabelnya kosong.
     *
     * 🔴 Inilah yang dipakai beranda, bukan label yang diketik tangan. Label
     * ketik memungkinkan badge berkata "Semester II 2024" sementara angka di
     * bawahnya berasal dari semester lain — pernyataan resmi yang keliru,
     * tanpa satu pun tanda di layar.
     *
     * @return array{tahun:int, semester:int}|null
     */
    public static function periodeTerbaru(): ?array
    {
        $p = static::periodeTersedia();

        return $p === [] ? null : ['tahun' => $p[0]['tahun'], 'semester' => $p[0]['semester']];
    }
}
