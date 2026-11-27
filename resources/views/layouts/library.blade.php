<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#102b68">
        <meta name="description" content="Explorez la bibliothèque numérique des mémoires de l'ENEAM. Recherchez les travaux académiques et consultez les notices publiées.">
        <title>@yield('title', 'Bibliothèque numérique · ENEAM')</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet">
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body>
        @include('components.library.header')
        <main>
            @if (session('status'))
                <div class="container flash-status" role="status">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="container flash-errors" role="alert">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif
            @yield('content')
        </main>
        @include('components.library.footer')
        @yield('dialogs')
        <div class="toast" id="toast" role="status" aria-live="polite"></div>
        @yield('scripts')
    </body>
</html>
