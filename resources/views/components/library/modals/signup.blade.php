<dialog class="modal" id="signup-dialog" aria-labelledby="signup-title" @if ($errors->register->any()) data-auto-open @endif>
            <div class="modal-head">
                <div>
                    <h2 id="signup-title">Créer un compte lecteur</h2>
                    <p>Inscrivez-vous pour retrouver vos accès et vos consultations.</p>
                </div>
                <button class="modal-close" type="button" aria-label="Fermer">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="m6 6 12 12M18 6 6 18"/></svg>
                </button>
            </div>
            <div class="modal-body">
                <form class="modal-form" id="signup-form" method="POST" action="{{ route('register') }}">
                    @csrf
                    <div class="field">
                        <label for="signup-first-name">Prénom</label>
                        <input id="signup-first-name" name="first_name" type="text" value="{{ old('first_name') }}" autocomplete="given-name" required>
                    </div>
                    <div class="field">
                        <label for="signup-last-name">Nom</label>
                        <input id="signup-last-name" name="last_name" type="text" value="{{ old('last_name') }}" autocomplete="family-name" required>
                    </div>
                    <div class="field">
                        <label for="signup-email">Adresse e-mail</label>
                        <input id="signup-email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
                    </div>
                    <div class="field">
                        <label for="signup-password">Mot de passe</label>
                        <input id="signup-password" name="password" type="password" autocomplete="new-password" minlength="8" required>
                    </div>
                    <div class="field">
                        <label for="signup-password-confirmation">Confirmer le mot de passe</label>
                        <input id="signup-password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required>
                    </div>
                    <label class="form-note">
                        <input type="checkbox" name="terms" required>
                        J’accepte les conditions d’utilisation et de consultation de la bibliothèque.
                    </label>
                    @if ($errors->register->any())
                        <div class="form-errors" role="alert">
                            @foreach ($errors->register->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif
                    <button class="button" type="submit">Créer mon compte</button>
                    <p class="form-note">Votre compte lecteur sera créé après validation des informations.</p>
                </form>
            </div>
        </dialog>
