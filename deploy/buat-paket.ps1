<#
    Membuat dua berkas ZIP siap unggah ke cPanel.

    Jalankan dari root project:
        powershell -ExecutionPolicy Bypass -File deploy\buat-paket.ps1

    Menghasilkan (di deploy\dist\):
        saibatin-app.zip         → diekstrak ke /home/<akun>/saibatin-app
        saibatin-public.zip      → diekstrak ke /home/<akun>/public_html

    🔴 Skrip ini TIDAK menyentuh server. Ia hanya mengemas berkas di laptop.
       Langkah unggah & cutover-nya ada di deploy/RUNBOOK-CPANEL.md dan memang
       dikerjakan sendiri oleh user (keputusan yang sudah dikunci).

    🔴 `.env` SENGAJA TIDAK IKUT. Kredensial database produksi berbeda dari
       lokal, dan mengirim `.env` lokal ke server berarti aplikasi produksi
       menunjuk ke DB laptop yang tidak akan pernah bisa dihubunginya. Salin
       `deploy/env-cpanel.txt` di server lalu isi nilainya di sana.
#>

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$dist = Join-Path $PSScriptRoot 'dist'
$kerja = Join-Path $env:TEMP ('saibatin-paket-' + [guid]::NewGuid().ToString('N').Substring(0, 8))

Write-Host "Root project : $root"
Write-Host "Folder kerja : $kerja`n"

# ── Prasyarat ────────────────────────────────────────────────────────────────
# Bundel Vite WAJIB dibangun ulang lebih dulu. Inertia yang tidak menemukan
# komponen halaman memberi HALAMAN PUTIH, bukan pesan galat — dan bundel
# tertinggal pernah lolos empat hari tanpa ada yang sadar (HANDOFF §5 no. 11).
if (-not (Test-Path (Join-Path $root 'public\build\manifest.json'))) {
    throw "public/build/manifest.json tidak ada. Jalankan `npm run build` dulu."
}

$manifestUmur = (Get-Date) - (Get-Item (Join-Path $root 'public\build\manifest.json')).LastWriteTime
$sumberBaru = Get-ChildItem (Join-Path $root 'resources') -Recurse -File -Include *.jsx, *.js, *.css |
    Where-Object { $_.LastWriteTime -gt (Get-Item (Join-Path $root 'public\build\manifest.json')).LastWriteTime }
if ($sumberBaru) {
    Write-Warning "Ada $($sumberBaru.Count) berkas di resources/ yang LEBIH BARU dari hasil build."
    Write-Warning "Jalankan `npm run build` lagi, atau halaman yang Anda ubah tidak akan ikut."
    if ((Read-Host 'Lanjut tetap? (y/N)') -ne 'y') { exit 1 }
}
Write-Host ("Bundel Vite  : umur {0:N1} jam" -f $manifestUmur.TotalHours)

# ── Paket 1: aplikasi (di luar public_html) ──────────────────────────────────
$app = Join-Path $kerja 'saibatin-app'
New-Item -ItemType Directory -Force -Path $app | Out-Null

# Hanya folder yang memang dibutuhkan runtime. `tests`, `_analisis`, `scripts`,
# `tessdata`, dan `node_modules` sengaja tidak ikut.
foreach ($d in @('app', 'bootstrap', 'config', 'database', 'lang', 'routes', 'resources', 'vendor')) {
    $sumber = Join-Path $root $d
    if (Test-Path $sumber) {
        Copy-Item $sumber -Destination $app -Recurse -Force
        Write-Host "  + $d"
    }
}
Copy-Item (Join-Path $root 'artisan') -Destination $app -Force
Copy-Item (Join-Path $root 'composer.json') -Destination $app -Force
Copy-Item (Join-Path $root 'composer.lock') -Destination $app -Force

# `storage` dikirim sebagai KERANGKA KOSONG. Isi storage lokal adalah berkas
# uji dan cache dari laptop; menimpanya ke produksi berarti membuang berkas
# warga yang sudah ada di sana saat deploy ulang.
foreach ($d in @(
    'storage\app\private', 'storage\app\public',
    'storage\framework\cache\data', 'storage\framework\sessions',
    'storage\framework\testing', 'storage\framework\views', 'storage\logs'
)) {
    New-Item -ItemType Directory -Force -Path (Join-Path $app $d) | Out-Null
}
Write-Host "  + storage (kerangka kosong)"

# Cache yang menempel pada path laptop — kalau ikut, aplikasi di server mencari
# berkas di C:\sam\... dan gagal total.
foreach ($f in @('bootstrap\cache\config.php', 'bootstrap\cache\routes-v7.php', 'bootstrap\cache\events.php')) {
    $p = Join-Path $app $f
    if (Test-Path $p) { Remove-Item $p -Force; Write-Host "  - $f (cache path laptop)" }
}

# ── Paket 2: isi public_html ─────────────────────────────────────────────────
$pub = Join-Path $kerja 'public_html'
New-Item -ItemType Directory -Force -Path $pub | Out-Null
Copy-Item (Join-Path $root 'public\*') -Destination $pub -Recurse -Force

# index.php diganti versi yang menunjuk ke luar public_html.
Copy-Item (Join-Path $PSScriptRoot 'index-public_html.php') -Destination (Join-Path $pub 'index.php') -Force
Copy-Item (Join-Path $PSScriptRoot 'htaccess-public_html.txt') -Destination (Join-Path $pub '.htaccess') -Force
Write-Host "  + public_html (index.php & .htaccess versi cPanel)"

# 🔴 `public/uploads` berisi gambar berita/galeri yang MEMANG publik, tapi di
# laptop folder itu juga menampung sisa berkas uji. Yang ikut hanya subfolder
# yang sah publik — daftarnya sama dengan FOLDER_PUBLIK di BerkasController.
$uploads = Join-Path $pub 'uploads'
if (Test-Path $uploads) {
    Get-ChildItem $uploads -Directory |
        Where-Object { $_.Name -notin @('produk', 'berita', 'galeri', 'gallery') } |
        ForEach-Object { Remove-Item $_.FullName -Recurse -Force; Write-Host "  - uploads/$($_.Name) (bukan folder publik)" }
}

# ── Kemas ────────────────────────────────────────────────────────────────────
New-Item -ItemType Directory -Force -Path $dist | Out-Null
Get-ChildItem $dist -Filter *.zip | Remove-Item -Force

<#
    🔴 ENTRI ZIP HARUS MEMAKAI FORWARD SLASH. Spesifikasi ZIP mewajibkannya,
    dan cPanel (Linux) menaatinya. Dua cara yang "jelas" DUA-DUANYA salah di
    Windows PowerShell 5.1 — keduanya sudah dicoba dan gagal 17 Agu 2026:

      Compress-Archive                  → 10.403 dari 10.406 entri backslash
      ZipFile::CreateFromDirectory      → 10.283 dari 10.286 entri backslash
                                          (.NET Framework 4.x; baru diperbaiki
                                           di .NET Core/5+, yang TIDAK dipakai
                                           Windows PowerShell)

    Gejalanya kalau lolos: ekstrak di server menghasilkan berkas bernama
    harfiah `app\Http\Controllers\X.php` menumpuk di satu folder datar.
    Aplikasinya mati total dan pesan galatnya tidak menyinggung ZIP sama sekali.

    Satu-satunya cara yang benar adalah menulis tiap entri sendiri dengan nama
    yang sudah dinormalkan. Pemeriksaan di bawah menjaga supaya ini tidak
    diam-diam kembali ke salah satu cara di atas.
#>
# Dua assembly, bukan satu: `ZipFile`/`ZipFileExtensions` ada di
# ...Compression.FileSystem, sementara `ZipArchive`/`ZipArchiveMode` ada di
# ...Compression. Memuat yang pertama saja membuat skrip gagal di tengah jalan
# dengan "Unable to find type [IO.Compression.ZipArchiveMode]".
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
$level = [IO.Compression.CompressionLevel]::Optimal

function New-ZipForwardSlash {
    param([string]$Sumber, [string]$Tujuan)

    $akar = (Resolve-Path $Sumber).Path.TrimEnd('\') + '\'
    $zip = [IO.Compression.ZipFile]::Open($Tujuan, [IO.Compression.ZipArchiveMode]::Create)
    try {
        foreach ($f in Get-ChildItem $Sumber -Recurse -File -Force) {
            $rel = $f.FullName.Substring($akar.Length).Replace('\', '/')
            [void][IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $f.FullName, $rel, $level)
        }
        # Folder kosong (kerangka storage/) tidak punya berkas, jadi tidak
        # terbawa oleh loop di atas — padahal Laravel butuh foldernya ada.
        foreach ($d in Get-ChildItem $Sumber -Recurse -Directory -Force) {
            if (-not (Get-ChildItem $d.FullName -Force | Select-Object -First 1)) {
                $rel = $d.FullName.Substring($akar.Length).Replace('\', '/') + '/'
                [void]$zip.CreateEntry($rel)
            }
        }
    } finally {
        $zip.Dispose()
    }
}

New-ZipForwardSlash -Sumber $app -Tujuan (Join-Path $dist 'saibatin-app.zip')
New-ZipForwardSlash -Sumber $pub -Tujuan (Join-Path $dist 'saibatin-public.zip')

# Verifikasi, bukan asumsi: kalau suatu saat baris di atas diganti lagi ke
# Compress-Archive, pemeriksaan ini yang akan menangkapnya sebelum paketnya
# terlanjur diunggah.
foreach ($z in @('saibatin-app.zip', 'saibatin-public.zip')) {
    $arsip = [IO.Compression.ZipFile]::OpenRead((Join-Path $dist $z))
    $salah = @($arsip.Entries | Where-Object { $_.FullName -match '\\' }).Count
    $total = $arsip.Entries.Count
    $arsip.Dispose()
    if ($salah -gt 0) { throw "$z memakai backslash pada $salah dari $total entri — tidak akan bisa diekstrak di Linux." }
    Write-Host "  $z : $total entri, pemisah jalur benar"
}

Remove-Item $kerja -Recurse -Force

Write-Host "`nSelesai:"
Get-ChildItem $dist -Filter *.zip | ForEach-Object {
    "{0,-24} {1,8:N1} MB" -f $_.Name, ($_.Length / 1MB)
}
Write-Host "`nLangkah berikutnya: deploy/RUNBOOK-CPANEL.md"
