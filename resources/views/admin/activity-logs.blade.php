@extends('layouts.library')

@section('title', 'Journal d’activité · Administration ENEAM')

@section('content')
    <section class="admin-page">
        <div class="container">
            @include('components.admin.navigation')
            <header class="admin-welcome">
                <div><span class="section-kicker">Administration ENEAM</span><h1>Journal d’activité</h1><p>Historique horodaté des opérations sensibles et de leur auteur.</p></div>
                <span class="account-badge">{{ $logs->total() }} événement(s)</span>
            </header>
            <section class="admin-panel">
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead><tr><th>Date</th><th>Acteur</th><th>Action</th><th>Objet</th><th>Détails</th></tr></thead>
                        <tbody>
                            @forelse ($logs as $log)
                                <tr>
                                    <td>{{ $log->occurred_at?->format('d/m/Y H:i:s') ?? '—' }}</td>
                                    <td>{{ $log->user?->name ?? 'Système' }}<small>{{ $log->user?->email ?? '' }}</small></td>
                                    <td><code>{{ $log->action }}</code></td>
                                    <td>{{ class_basename($log->subject_type ?? '') ?: '—' }}{{ $log->subject_id ? ' #'.$log->subject_id : '' }}</td>
                                    <td><details class="admin-details"><summary>Consulter</summary><pre class="admin-log-details">{{ json_encode($log->metadata ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></details></td>
                                </tr>
                            @empty
                                <tr><td colspan="5"><div class="admin-empty">Aucune opération enregistrée.</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="admin-pagination">{{ $logs->links() }}</div>
            </section>
        </div>
    </section>
@endsection
