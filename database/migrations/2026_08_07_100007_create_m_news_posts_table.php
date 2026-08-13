<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Berita/artikel. Nama tabel `m_news_posts` warisan Laravel 9 asli — dipertahankan. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('m_news_posts', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('judul');
            $table->string('slug');
            $table->string('kategori')->nullable();
            $table->text('ringkasan')->nullable();
            $table->longText('konten');
            $table->string('gambar')->nullable();
            $table->string('penulis')->nullable();
            $table->boolean('publish')->default(false);
            $table->dateTime('created_at', 3)->useCurrent();
            $table->dateTime('updated_at', 3);

            $table->unique('slug', 'm_news_posts_slug_key');
            $table->index('publish', 'm_news_posts_publish_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_news_posts');
    }
};
