<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Masuk') - Si-Pilah Bank Sampah Induk</title>
    
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Separated Vanilla CSS -->
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body>
    <main class="auth-wrapper">
        @yield('content')
    </main>

    <!-- Separated Vanilla JS -->
    <script src="{{ asset('js/auth.js') }}" defer></script>
</body>
</html>