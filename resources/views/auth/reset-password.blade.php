@extends('layouts.library')

@section('title', 'Réinitialiser le mot de passe · ENEAM')

@section('content')
    <section class="dashboard-page">
        <div class="container">
            <div class="dashboard-welcome">
                <div>
                    <span class="section-kicker">Sécurité du compte</span>
                    <h1>Choisir un nouveau mot de passe</h1>
                    <p>Définissez un nouveau mot de passe pour votre compte lecteur.</p>
                </div>
            </div>

            <article class="dashboard-card">
                <form class="modal-form" method="POST" action="{{ route('password.update') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <div class="field">
                        <label for="reset-email">Adresse e-mail</label>
                        <input id="reset-email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="email" required>
                    </div>
                    <div class="field">
                        <label for="reset-password">Nouveau mot de passe</label>
                        <input id="reset-password" name="password" type="password" autocomplete="new-password" required>
                    </div>
                    <div class="field">
                        <label for="reset-password-confirmation">Confirmer le mot de passe</label>
                        <input id="reset-password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                    </div>
                    <button class="button" type="submit">Enregistrer le nouveau mot de passe</button>
                </form>
            </article>
        </div>
    </section>
@endsection
