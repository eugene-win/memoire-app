@extends('layouts.library')

@section('title', 'Visionneuse PDF · ENEAM')

@section('content')
    <section class="pdf-viewer-page">
        <div class="container">
            <div class="pdf-viewer-heading">
                <div>
                    <span class="section-kicker">Consultation protégée</span>
                    <h1>{{ $version->metadata_snapshot['title'] ?? $thesis->title }}</h1>
                    <p>{{ $version->metadata_snapshot['author_name'] ?? $thesis->author_name }} · {{ $thesis->department }} · {{ $thesis->defended_year }}</p>
                </div>
                <a class="button button-outline" href="{{ route('dashboard') }}">Retour à mon espace</a>
            </div>
            <div class="pdf-viewer-frame">
                <iframe
                    src="{{ route('thesis-versions.document', $version) }}"
                    title="Document PDF : {{ $thesis->title }}"
                    referrerpolicy="no-referrer"
                ></iframe>
            </div>
            <p class="pdf-viewer-note">Le document est servi depuis un stockage privé et reste accessible uniquement aux profils autorisés.</p>
        </div>
    </section>
@endsection
