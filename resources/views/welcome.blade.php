@extends('layouts.library')

@section('title', 'Bibliothèque numérique · ENEAM')

@section('content')
    @include('components.library.hero')
    @include('components.library.benefits')
    @include('components.library.catalog')
    @include('components.library.deposit-banner')
@endsection

@section('dialogs')
    @include('components.library.modals.auth')
    @include('components.library.modals.reset')
    @include('components.library.modals.signup')
    @include('components.library.modals.deposit')
    @include('components.library.modals.detail')
    @include('components.library.modals.reader')
@endsection
