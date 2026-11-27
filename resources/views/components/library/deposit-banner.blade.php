<section class="container" id="deposer">
                <div class="deposit-banner">
                    <div class="deposit-copy">
                        <span class="section-kicker">Valoriser votre travail</span>
                        <h2>Votre mémoire a sa place dans la bibliothèque.</h2>
                        <p>Le dépôt est réservé aux étudiants de <strong>Licence 3</strong> ayant soutenu et disposant d’une autorisation active. Chaque mémoire suit un contrôle avant sa publication.</p>
                    </div>
                    @auth
                        @if (auth()->user()->role === 'student')
                            <a class="button" href="{{ route('dashboard') }}#deposer">
                                Vérifier mon éligibilité
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M5 12h14M13 6l6 6-6 6"/>
                                </svg>
                            </a>
                        @else
                            <span class="deposit-banner-note">Connectez-vous avec un compte étudiant ENEAM éligible.</span>
                        @endif
                    @else
                        <a class="button" href="{{ route('login') }}">
                            Connexion étudiant
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M5 12h14M13 6l6 6-6 6"/>
                            </svg>
                        </a>
                    @endauth
                </div>
            </section>
