<header class="site-header">
            <div class="container header-inner">
                <a class="brand" href="{{ route('home') }}" aria-label="ENEAM — Bibliothèque numérique, accueil">
                    <img class="brand-logo" src="{{ asset('images/eneam-logo.png') }}" alt="Logo de l’ENEAM">
                    <span class="brand-copy">
                        <strong>École Nationale d’Économie<br>Appliquée et de Management</strong>
                        <small>Bibliothèque numérique des mémoires</small>
                    </span>
                </a>

                <nav class="main-nav" id="main-nav" aria-label="Navigation principale">
                    <a href="{{ url('/#catalogue') }}">Catalogue</a>
                    <a href="{{ url('/#fonctionnement') }}">Fonctionnement</a>
                    <a href="{{ url('/#deposer') }}">Déposer un mémoire</a>
                </nav>

                <div class="header-actions">
                    @auth
                        <a class="button button-outline" href="{{ auth()->user()->hasRole('administrator') ? route('admin.index') : route('dashboard') }}">
                            {{ auth()->user()->hasRole('administrator') ? 'Administration' : 'Mon espace' }}
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="button" type="submit">Se déconnecter</button>
                        </form>
                    @else
                        <button class="button button-outline" type="button" data-open-dialog="#signup-dialog">Créer un compte</button>
                        <a class="button" href="{{ route('login') }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                                <path d="M10 17l5-5-5-5M15 12H3"/>
                            </svg>
                            Se connecter
                        </a>
                    @endauth
                    <button class="mobile-toggle" id="mobile-toggle" type="button" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="main-nav">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                            <path d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </header>
