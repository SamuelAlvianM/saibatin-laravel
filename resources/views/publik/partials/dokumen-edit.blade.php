{{--
  Panel dokumen MODE EDIT — hanya untuk Super Admin, dan di dalamnya hanya
  tampil kalau mode edit menyala (island yang memutuskan).

  Dipasang di bawah tabel berkas halaman publik yang punya kategori dokumen,
  sehingga petugas menambah/menghapus berkas halaman itu tanpa berpindah ke
  dashboard Dokumen Publikasi dan menebak kategorinya.

  @param array $jenis  kategori dokumen halaman ini (t_produk.jenis)
--}}
@if (! empty($jenis) && auth()->check() && auth()->user()->isSuperAdmin())
    <div data-island="DokumenEdit" data-props='@json(['jenis' => array_values($jenis)])'></div>
@endif
