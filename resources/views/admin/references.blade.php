@extends('layouts.library')

@section('title', 'Référentiels académiques · Administration ENEAM')

@section('content')
    <section class="admin-page">
        <div class="container">
            @include('components.admin.navigation')
            <header class="admin-welcome">
                <div><span class="section-kicker">Administration ENEAM</span><h1>Référentiels académiques</h1><p>Maintenez les filières, niveaux et années utilisés dans les profils et les dépôts.</p></div>
            </header>
            <div class="admin-feedback" role="status" aria-live="polite" hidden></div>

            @php
                $referenceGroups = [
                    ['type' => 'filieres', 'title' => 'Filières', 'records' => $filieres],
                    ['type' => 'study-levels', 'title' => 'Niveaux d’étude', 'records' => $study_levels],
                    ['type' => 'defense-years', 'title' => 'Années de soutenance', 'records' => $defense_years],
                ];
            @endphp

            @foreach ($referenceGroups as $group)
                <section class="admin-panel admin-reference-panel">
                    <div class="admin-panel-heading"><div><h2>{{ $group['title'] }}</h2><p>{{ $group['records']->count() }} entrée(s)</p></div></div>
                    <form class="admin-reference-create" method="POST" action="{{ route('admin.references.store', $group['type']) }}" data-admin-form>
                        @csrf
                        @if ($group['type'] === 'defense-years')
                            <div class="field"><label for="new-label-{{ $group['type'] }}">Libellé de l’année</label><input id="new-label-{{ $group['type'] }}" name="label" maxlength="20" required></div>
                            <div class="field"><label for="new-start-{{ $group['type'] }}">Début (facultatif)</label><input id="new-start-{{ $group['type'] }}" name="starts_on" type="date"></div>
                            <div class="field"><label for="new-end-{{ $group['type'] }}">Fin (facultatif)</label><input id="new-end-{{ $group['type'] }}" name="ends_on" type="date"></div>
                        @else
                            <div class="field"><label for="new-code-{{ $group['type'] }}">Code</label><input id="new-code-{{ $group['type'] }}" name="code" maxlength="{{ $group['type'] === 'filieres' ? 32 : 16 }}" required></div>
                            <div class="field"><label for="new-name-{{ $group['type'] }}">Nom</label><input id="new-name-{{ $group['type'] }}" name="name" maxlength="255" required></div>
                        @endif
                        <button class="button" type="submit">Ajouter</button>
                    </form>

                    <div class="admin-table-wrap">
                        <table class="admin-table">
                            <thead><tr><th>Référentiel</th><th>Statut</th><th>Modifier</th></tr></thead>
                            <tbody>
                                @forelse ($group['records'] as $record)
                                    <tr>
                                        <td><strong>{{ $group['type'] === 'defense-years' ? $record->label : $record->name }}</strong><small>{{ $group['type'] === 'defense-years' ? trim(($record->starts_on?->format('d/m/Y') ?? '').' – '.($record->ends_on?->format('d/m/Y') ?? '')) : $record->code }}</small></td>
                                        <td>
                                            <form class="admin-inline-form" method="POST" action="{{ route('admin.references.status', ['type' => $group['type'], 'id' => $record->id]) }}" data-admin-form>
                                                @csrf @method('PATCH')
                                                <select name="is_active" aria-label="Statut de {{ $group['title'] }}"><option value="1" @selected($record->is_active)>Actif</option><option value="0" @selected(! $record->is_active)>Inactif</option></select>
                                                <button class="button button-outline" type="submit">Appliquer</button>
                                            </form>
                                        </td>
                                        <td>
                                            <details class="admin-details">
                                                <summary>Modifier</summary>
                                                <form class="admin-action-form" method="POST" action="{{ route('admin.references.update', ['type' => $group['type'], 'id' => $record->id]) }}" data-admin-form>
                                                    @csrf @method('PUT')
                                                    @if ($group['type'] === 'defense-years')
                                                        <div class="field"><label for="label-{{ $record->id }}">Libellé</label><input id="label-{{ $record->id }}" name="label" value="{{ $record->label }}" required></div>
                                                        <div class="field"><label for="start-{{ $record->id }}">Début</label><input id="start-{{ $record->id }}" name="starts_on" type="date" value="{{ $record->starts_on?->format('Y-m-d') }}"></div>
                                                        <div class="field"><label for="end-{{ $record->id }}">Fin</label><input id="end-{{ $record->id }}" name="ends_on" type="date" value="{{ $record->ends_on?->format('Y-m-d') }}"></div>
                                                    @else
                                                        <div class="field"><label for="code-{{ $record->id }}">Code</label><input id="code-{{ $record->id }}" name="code" value="{{ $record->code }}" required></div>
                                                        <div class="field"><label for="name-{{ $record->id }}">Nom</label><input id="name-{{ $record->id }}" name="name" value="{{ $record->name }}" required></div>
                                                    @endif
                                                    <button class="button" type="submit">Enregistrer</button>
                                                </form>
                                            </details>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3"><div class="admin-empty">Aucune entrée enregistrée.</div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            @endforeach
        </div>
    </section>
@endsection

