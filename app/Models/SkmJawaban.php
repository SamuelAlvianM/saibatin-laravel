<?php

namespace App\Models;

use App\Models\Concerns\PresisiMilidetik;
use Illuminate\Database\Eloquent\Model;

/**
 * Jawaban Survei Kepuasan Masyarakat.
 *
 * 🔴 `jawaban` (JSON) memakai TIGA bentuk kunci yang harus sama-sama dihitung:
 *   - kuesioner dinas 2026 : "p1".."p16"  (16 pertanyaan, berkas Word 17 Agu)
 *   - warisan lokal        : "0".."8"     (204 responden, 9 unsur)
 *   - warisan produksi     : "u0".."u8"   (203 responden)
 * Di project saudara, mengabaikan bentuk warisan membuat nilai IKM tampil
 * 0,00 / mutu D padahal datanya ada. Rumusnya juga bukan rata-rata dibagi 5,
 * melainkan NRR × 25 (Permenpan RB 14/2017).
 *
 * Pembacaan ketiganya dipusatkan di `nilaiTerbaca()` — jangan menguraikan
 * bentuk kunci di controller, karena bentuk keempat pasti muncul lagi.
 */
class SkmJawaban extends Model
{
    use PresisiMilidetik;

    protected $table = 't_skm_jawaban';

    public const UPDATED_AT = null;

    protected $fillable = [
        'nama', 'instansi', 'umur', 'jenis_kelamin', 'pendidikan', 'pekerjaan',
        'produk_layanan', 'disabilitas', 'jenis_disabilitas', 'jawaban', 'saran',
    ];

    protected function casts(): array
    {
        return [
            'jawaban' => 'array',
            'umur' => 'integer',
            'disabilitas' => 'boolean',
        ];
    }

    /**
     * Nilai yang benar-benar terbaca dari satu responden, apa pun generasi
     * kuesionernya.
     *
     * @return array{generasi:'baru'|'warisan'|'kosong', nilai:array<string,int>}
     */
    public function nilaiTerbaca(): array
    {
        $jawaban = is_array($this->jawaban) ? $this->jawaban : [];
        $maks = (int) config('skm.skala_max');
        $sah = fn ($v) => is_numeric($v) && (int) $v >= 1 && (int) $v <= $maks;

        $baru = [];
        foreach (config('skm.pertanyaan') as $p) {
            if ($sah($jawaban[$p['kunci']] ?? null)) {
                $baru[$p['kunci']] = (int) $jawaban[$p['kunci']];
            }
        }

        if ($baru !== []) {
            return ['generasi' => 'baru', 'nilai' => $baru];
        }

        // Warisan: dua penulisan kunci untuk daftar unsur yang sama.
        $lama = [];
        foreach (array_keys(config('skm.warisan')) as $i) {
            $v = $jawaban[(string) $i] ?? $jawaban['u'.$i] ?? null;
            if ($sah($v)) {
                $lama[(string) $i] = (int) $v;
            }
        }

        return $lama === []
            ? ['generasi' => 'kosong', 'nilai' => []]
            : ['generasi' => 'warisan', 'nilai' => $lama];
    }

    /** Rata-rata skor responden ini (0 bila tidak ada jawaban yang sah). */
    public function rataSkor(): float
    {
        $nilai = $this->nilaiTerbaca()['nilai'];

        return $nilai === [] ? 0.0 : round(array_sum($nilai) / count($nilai), 2);
    }
}
