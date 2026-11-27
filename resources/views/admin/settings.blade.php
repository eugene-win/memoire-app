@extends('layouts.library')

@section('title', 'Paramètres · Administration ENEAM')

@section('content')
    <section class="admin-page">
        <div class="container">
            @include('components.admin.navigation')
            <header class="admin-welcome">
                <div><span class="section-kicker">Administration ENEAM</span><h1>Paramètres de la plateforme</h1><p>Configurez les valeurs métier disponibles, sans exposer de secrets d’intégration.</p></div>
            </header>
            <div class="admin-feedback" role="status" aria-live="polite" hidden></div>
            <section class="admin-panel">
                <div class="admin-panel-heading"><div><h2>Valeurs configurables</h2><p>Chaque modification est conservée dans le journal d’activité.</p></div></div>
                <div class="admin-settings-grid">
                    @foreach ($settings as $setting)
                        <article class="admin-setting-card">
                            <div><span class="section-kicker">{{ $setting->setting_group }}</span><h3>{{ $setting->label }}</h3><p>{{ $setting->description }}</p></div>
                            @if (in_array($setting->setting_key, ['external_access_price_xof', 'maximum_pdf_size_bytes', 'preview_page_count'], true))
                                <form class="admin-setting-form" method="POST" action="{{ route('admin.settings.update', $setting) }}" data-admin-form>
                                    @csrf @method('PATCH')
                                    <div class="field">
                                        <label for="setting-{{ $setting->id }}">Valeur</label>
                                        <input id="setting-{{ $setting->id }}" name="value" type="number" value="{{ $setting->value }}" min="{{ $setting->setting_key === 'preview_page_count' ? 1 : ($setting->setting_key === 'maximum_pdf_size_bytes' ? 1024 : 1) }}" max="{{ $setting->setting_key === 'preview_page_count' ? 50 : ($setting->setting_key === 'maximum_pdf_size_bytes' ? 5242880 : 10000000) }}" required>
                                    </div>
                                    <button class="button" type="submit">Enregistrer</button>
                                </form>
                            @else
                                <p class="admin-readonly-value">{{ $setting->value }}</p>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
            <aside class="admin-notice">
                <strong>Intégrations sécurisées</strong>
                <p>Les identifiants KKiaPay et les secrets anti-plagiat doivent être fournis dans l’environnement serveur. Le seuil anti-plagiat de 20 % n’est pas modifiable depuis l’interface.</p>
            </aside>
        </div>
    </section>
@endsection

