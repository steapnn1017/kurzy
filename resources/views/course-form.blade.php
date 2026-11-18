{{-- =====================================================================
     resources/views/course-form.blade.php = FORMULÁŘ KURZU (nový i úprava).
     Jedna šablona pro obě akce, rozlišíme je přes $course->exists:
       false = nový kurz  → POST /courses              (CourseController@store)
       true  = úprava     → PUT  /courses/{id}         (CourseController@update)
     Při chybě validace Laravel vrátí uživatele sem, old('pole') vrátí
     to, co napsal, a @error('pole') ukáže hlášku pod polem.
     ===================================================================== --}}

{{-- Společný layout --}}
@extends('layout')

{{-- Titulek záložky podle toho, jestli kurz už existuje --}}
@section('title', $course->exists ? 'Upravit kurz' : 'Nový kurz')

@section('content')
    {{-- Zpět: u úpravy na detail kurzu, u nového na seznam --}}
    <a class="back" href="{{ $course->exists ? route('courses.show', $course) : route('courses.index') }}">← Zpět</a>

    {{-- Nadpis --}}
    <header class="page-head">
        <div>
            <h1>{{ $course->exists ? 'Upravit kurz' : 'Nový kurz' }}</h1>
            <p class="lead">Kód kurzu musí být jedinečný a kapacita musí být celé číslo větší než 0.</p>
        </div>
    </header>

    {{-- Formulář. novalidate = prohlížeč nekontroluje sám, kontrolu dělá server (české hlášky). --}}
    <form class="panel form" method="POST" action="{{ $course->exists ? route('courses.update', $course) : route('courses.store') }}" novalidate>
        {{-- Ochranný CSRF token --}}
        @csrf
        {{-- U úpravy pošleme skryté _method=PUT (HTML formulář sám PUT neumí) --}}
        @if ($course->exists)
            @method('PUT')
        @endif

        {{-- Název kurzu --}}
        <div class="field">
            <label for="name">Název kurzu</label>
            {{-- old('name', $course->name) = po chybě to, co uživatel napsal, jinak uložená hodnota --}}
            <input @class(['input', 'invalid' => $errors->has('name')]) id="name" name="name" value="{{ old('name', $course->name) }}" placeholder="např. Základy programování v Pythonu" autofocus>
            {{-- Chybová hláška pod polem --}}
            @error('name') <p class="error">{{ $message }}</p> @enderror
        </div>

        {{-- Kód a kapacita vedle sebe --}}
        <div class="form-row">
            {{-- Kód kurzu --}}
            <div class="field">
                <label for="code">Kód kurzu</label>
                {{-- autocapitalize = mobilní klávesnice píše velká písmena --}}
                <input @class(['input', 'invalid' => $errors->has('code')]) id="code" name="code" value="{{ old('code', $course->code) }}" placeholder="PY-101" autocapitalize="characters">
                <p class="hint">Jedinečný, uloží se velkými písmeny.</p>
                @error('code') <p class="error">{{ $message }}</p> @enderror
            </div>

            {{-- Kapacita --}}
            <div class="field">
                <label for="capacity">Kapacita</label>
                {{-- type=number + inputmode = na mobilu číselná klávesnice --}}
                <input @class(['input', 'invalid' => $errors->has('capacity')]) type="number" min="1" step="1" inputmode="numeric" id="capacity" name="capacity" value="{{ old('capacity', $course->capacity) }}" placeholder="12">
                {{-- U úpravy ukážeme, kolik je aktivních přihlášek (pod to kapacita nejde) --}}
                <p class="hint">{{ $course->exists ? 'Aktivních přihlášek: '.$course->activeCount() : 'Celé číslo větší než 0.' }}</p>
                @error('capacity') <p class="error">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Začátek kurzu: pole datetime-local chce formát 2026-11-18T17:00, proto format('Y-m-d\TH:i').
             ?-> = když starts_at chybí (nový kurz), nic se nevolá a pole zůstane prázdné. --}}
        <div class="field">
            <label for="starts_at">Začátek kurzu</label>
            <input @class(['input', 'invalid' => $errors->has('starts_at')]) type="datetime-local" id="starts_at" name="starts_at" value="{{ old('starts_at', $course->starts_at?->format('Y-m-d\TH:i')) }}">
            @error('starts_at') <p class="error">{{ $message }}</p> @enderror
        </div>

        {{-- Tlačítka --}}
        <div class="form-actions">
            <button class="btn btn-primary" type="submit">{{ $course->exists ? 'Uložit změny' : 'Vytvořit kurz' }}</button>
            <a class="btn btn-ghost" href="{{ $course->exists ? route('courses.show', $course) : route('courses.index') }}">Zrušit</a>
        </div>
    </form>
@endsection
