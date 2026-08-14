{{--
  Tabel dokumen publikasi (`t_produk`) yang menempel pada sebuah halaman.

  Berkasnya dipetakan dari ALAMAT halaman lewat `config/dokumen.php`, bukan
  ditebak dari slug — satu halaman bisa menampilkan beberapa kategori dan satu
  kategori bisa muncul di beberapa halaman (SOP ada di Produk maupun PPID).

  @param \Illuminate\Support\Collection $berkas
  @param string $judul  judul seksi, dibedakan saat satu halaman punya dua tabel
--}}

@if ($berkas->isNotEmpty())
    <div class="pt-2">
        <h2 class="mb-3 text-sm font-semibold text-slate-900">{{ $judul ?? 'Berkas' }}</h2>
        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="px-4 py-3 font-semibold">Nama Berkas</th>
                        <th class="whitespace-nowrap px-4 py-3 font-semibold">Tanggal Unggah</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($berkas as $b)
                        <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50/70">
                            <td class="px-4 py-3">
                                {{-- Dokumen publikasi memang untuk diunduh siapa saja;
                                     berkasnya disajikan langsung dari public/. --}}
                                <a href="{{ $b->file }}" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex items-center gap-2 font-medium text-brand hover:underline">
                                    <x-ikon nama="unduh" class="h-4 w-4 flex-shrink-0" />
                                    {{ $b->judul }}
                                </a>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">
                                {{ $b->created_at?->translatedFormat('j F Y, H:i') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="border-t border-slate-100 pt-4 text-sm text-slate-500">
        Dokumen resmi belum tersedia secara digital di portal ini. Untuk informasi lengkap,
        silakan <a href="/hubungi-kami" class="text-brand hover:underline">hubungi Disdukcapil
        Pesisir Barat</a> secara langsung.
    </div>
@endif
