{{-- =====================================================================
     resources/views/participant-form.blade.php = FORMULÁŘ ÚČASTNÍKA (nový i úprava).
     $participant->exists rozlišuje akci:
       false = nový   → POST /participants             (ParticipantController@store)
       true  = úprava → PUT  /participants/{id}        (ParticipantController@update)
     ===================================================================== --}}

{{-- Společný layout --}}
@extends('layout')

{{-- Titulek záložky --}}
@section('title', $participant->exists ? 'Upravit účastníka' : 'Nový účastník')

@section('content')
    {{-- Zpět na seznam účastníků --}}
    <a class="back" href="{{ route('participants.index') }}">← Zpět</a>

    {{-- Nadpis --}}
    <header class="page-head">
        <div>
            <h1>{{ $participant->exists ? 'Upravit účastníka' : 'Nový účastník' }}</h1>
            <p class="lead">E-mail musí být platný a v aplikaci jen jednou.</p>
        </div>
    </header>

    {{-- Formulář. novalidate = kontrolu dělá server (české hlášky pod poli). --}}
    <form class="panel form" method="POST" action="{{ $participant->exists ? route('participants.update', $participant) : route('participants.store') }}" novalidate>
        {{-- Ochranný CSRF token --}}
        @csrf
        {{-- U úpravy skryté _method=PUT --}}
        @if ($participant->exists)
            @method('PUT')
        @endif

        {{-- Jméno --}}
        <div class="field">
            <label for="name">Jméno a příjmení</label>
            {{-- autocomplete = prohlížeč může nabídnout uložené jméno --}}
            <input @class(['input', 'invalid' => $errors->has('name')]) id="name" name="name" value="{{ old('name', $participant->name) }}" placeholder="např. Jana Nováková" autocomplete="name" autofocus>
            @error('name') <p class="error">{{ $message }}</p> @enderror
        </div>

        {{-- E-mail. type="email" = na mobilu klávesnice se zavináčem (kontrolu formátu dělá server). --}}
        <div class="field">
            <label for="email">E-mail</label>
            <input @class(['input', 'invalid' => $errors->has('email')]) type="email" id="email" name="email" value="{{ old('email', $participant->email) }}" placeholder="jana.novakova@example.com" autocomplete="email">
            @error('email') <p class="error">{{ $message }}</p> @enderror
        </div>

        {{-- Tlačítka --}}
        <div class="form-actions">
            <button class="btn btn-primary" type="submit">{{ $participant->exists ? 'Uložit změny' : 'Přidat účastníka' }}</button>
            <a class="btn btn-ghost" href="{{ route('participants.index') }}">Zrušit</a>
        </div>
    </form>
@endsection
