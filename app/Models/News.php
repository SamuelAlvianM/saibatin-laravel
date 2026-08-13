<?php

namespace App\Models;

use App\Models\Concerns\PresisiMilidetik;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Berita/artikel. Nama tabel `m_news_posts` warisan Laravel 9 asli. */
class News extends Model
{
    use PresisiMilidetik;

    protected $table = 'm_news_posts';

    protected $fillable = [
        'judul', 'slug', 'kategori', 'ringkasan', 'konten', 'gambar',
        'penulis', 'publish',
    ];

    protected function casts(): array
    {
        return ['publish' => 'boolean'];
    }

    /** Hanya yang sudah terbit — dipakai seluruh halaman publik. */
    public function scopeTerbit(Builder $q): Builder
    {
        return $q->where('publish', true);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
