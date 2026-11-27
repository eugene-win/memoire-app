<dialog class="modal" id="auth-dialog" aria-labelledby="auth-title" @if ((request()->routeIs('login') || $errors->login->any()) && ! session('password-reset-status') && ! $errors->passwordReset->any()) data-auto-open @endif>
            <div class="modal-head">
                <div>
                    <h2 id="auth-title">Accéder à votre espace</h2>
                    <p>Choisissez votre profil pour continuer.</p>
                </div>
                <button class="modal-close" type="button" aria-label="Fermer">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="m6 6 12 12M18 6 6 18"/></svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="role-switch" aria-label="Type de compte">
                    <button type="button" data-role="student" aria-pressed="{{ old('account_type', 'external_reader') === 'student' ? 'true' : 'false' }}">Étudiant ENEAM</button>
                    <button type="button" data-role="validator" aria-pressed="{{ in_array(old('account_type'), ['validator', 'library_manager'], true) ? 'true' : 'false' }}">Responsable de filière et de bibliothèque</button>
                    <button type="button" data-role="external_reader" aria-pressed="{{ old('account_type', 'external_reader') === 'external_reader' ? 'true' : 'false' }}">Lecteur externe</button>
                </div>
                <form class="modal-form" id="login-form" method="POST" action="{{ route('login.store') }}">
                    @csrf
                    <input id="account-type" name="account_type" type="hidden" value="{{ old('account_type', 'external_reader') }}">
                    <div class="field">
                        <label for="login-identifier">{{ old('account_type', 'external_reader') === 'external_reader' ? 'Adresse e-mail' : 'Matricule professionnel ou étudiant' }}</label>
                        <input id="login-identifier" name="identifier" type="{{ old('account_type', 'external_reader') === 'external_reader' ? 'email' : 'text' }}" value="{{ old('identifier') }}" placeholder="{{ old('account_type', 'external_reader') === 'external_reader' ? 'nom@exemple.com' : 'Votre matricule' }}" autocomplete="username" required>
                    </div>
                    <div class="field">
                        <label for="login-password">Mot de passe</label>
                        <input id="login-password" name="password" type="password" placeholder="Votre mot de passe" autocomplete="current-password" required>
                    </div>
                    @if ($errors->login->any())
                        <div class="form-errors" role="alert">
                            @foreach ($errors->login->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif
                    <p class="form-note">Les étudiants et responsables se connectent avec un compte institutionnel préactivé. Les lecteurs externes utilisent leur e-mail.</p>
                    <div class="auth-links">
                        <button class="link-button" type="button" data-open-dialog="#signup-dialog">Créer un compte lecteur</button>
                        <button class="link-button" type="button" data-open-dialog="#reset-dialog">Mot de passe oublié ?</button>
                    </div>
                    <button class="button" type="submit">Continuer</button>
                </form>
            </div>
        </dialog>
