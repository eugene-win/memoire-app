<dialog class="modal" id="auth-dialog" aria-labelledby="auth-title" @if ((request()->routeIs('login') || $errors->login->any() || session('student-login-code-status')) && ! session('password-reset-status') && ! $errors->passwordReset->any()) data-auto-open @endif>
            <div class="modal-head">
                <div>
                    <h2 id="auth-title">Accéder à votre espace</h2>
                    <p>Étudiants : matricule et code e-mail. Visiteurs et personnel : identifiant et mot de passe.</p>
                </div>
                <button class="modal-close" type="button" aria-label="Fermer">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="m6 6 12 12M18 6 6 18"/></svg>
                </button>
            </div>
            <div class="modal-body">
                @if (session('student_login_challenge'))
                    <form class="modal-form" id="student-code-form" method="POST" action="{{ route('login.student-code.verify') }}">
                        @csrf
                        <p class="form-note" role="status">{{ session('student-login-code-status') }}</p>
                        <div class="field">
                            <label for="student-verification-code">Code de vérification</label>
                            <input id="student-verification-code" name="verification_code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required autofocus>
                        </div>
                        @if ($errors->login->any())
                            <div class="form-errors" role="alert">
                                @foreach ($errors->login->all() as $error)
                                    <p>{{ $error }}</p>
                                @endforeach
                            </div>
                        @endif
                        <button class="button" type="submit">Vérifier le code</button>
                        <div class="auth-links">
                            <button class="link-button" type="submit" form="resend-student-code-form">Renvoyer le code</button>
                            <button class="link-button" type="button" data-return-to-login>Changer de matricule</button>
                        </div>
                    </form>
                    <form id="resend-student-code-form" method="POST" action="{{ route('login.student-code.send') }}" hidden>
                        @csrf
                        <input type="hidden" name="matricule" value="{{ session('student_login_matricule') }}">
                    </form>
                @endif
                <form class="modal-form" id="login-form" method="POST"
                    action="{{ session('student_login_challenge') ? route('login.student-code.send') : route('login.store') }}"
                    data-password-action="{{ route('login.store') }}"
                    data-student-code-action="{{ route('login.student-code.send') }}"
                    @if (session('student_login_challenge')) hidden @endif>
                    @csrf
                    @if (session('student-login-code-status') && ! session('student_login_challenge'))
                        <p class="form-note" role="status">{{ session('student-login-code-status') }}</p>
                    @endif
                    <div class="field">
                        <label for="login-identifier">Adresse e-mail ou matricule</label>
                        <input id="login-identifier" name="identifier" type="text" value="{{ old('identifier', old('matricule')) }}" placeholder="nom@exemple.com ou matricule" autocomplete="username" required>
                    </div>
                    <div class="field" id="login-password-field" @if (! str_contains(old('identifier', ''), '@')) hidden @endif>
                        <label for="login-password">Mot de passe</label>
                        <input id="login-password" name="password" type="password" placeholder="Votre mot de passe" autocomplete="current-password" @required(str_contains(old('identifier', ''), '@'))>
                    </div>
                    @if ($errors->login->any() && ! session('student_login_challenge'))
                        <div class="form-errors" role="alert">
                            @foreach ($errors->login->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif
                    <p class="form-note" id="login-method-note">Un code à usage unique sera envoyé à l’adresse associée au matricule.</p>
                    <div class="auth-links">
                        <button class="link-button" type="button" data-open-dialog="#signup-dialog">Créer un compte visiteur</button>
                        <button class="link-button" id="password-reset-link" type="button" data-open-dialog="#reset-dialog">Mot de passe oublié ?</button>
                    </div>
                    <button class="link-button" id="staff-password-toggle" type="button" @if (str_contains(old('identifier', ''), '@')) hidden @endif>Personnel ENEAM : connexion par mot de passe</button>
                    <button class="button" id="login-submit" type="submit">
                        {{ str_contains(old('identifier', ''), '@') ? 'Se connecter' : 'Recevoir un code' }}
                    </button>
                </form>
            </div>
        </dialog>
