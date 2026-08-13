<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Dibaca komponen yang memanggil endpoint lewat fetch (mis. Cek Status),
         di luar jalur form Inertia yang menyisipkan tokennya sendiri. --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title inertia>{{ config('app.name') }}</title>

    {{-- Ikon tab peramban. `favicon.ico` bawaan Laravel sengaja DIBUANG: berkas
         itu 0 byte, dan peramban yang menemukannya berhenti mencari ikon lain
         sehingga tab tampil kosong walau icon.png sudah ada. --}}
    <link rel="icon" type="image/png" href="/icon.png">
    <link rel="apple-touch-icon" href="/icon.png">
    <meta name="theme-color" content="#1b4b72">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    @inertiaHead
</head>
<body class="antialiased">
    @inertia
</body>
</html>
