{{-- =====================================================================
     resources/views/participants.blade.php = SEZNAM ÚČASTNÍKŮ (adresa /participants).
     Data posílá ParticipantController@index v proměnné $participants
     (každý účastník má načtené přihlášky i s kurzy).
     U každého účastníka jsou štítky kurzů, barva štítku = stav přihlášky.
     ===================================================================== --}}

{{-- Společný layout --}}
@extends('layout')

{{-- Titulek záložky --}}
@section('title', 'Účastníci')

@section('content')
    {{-- Hlavička --}}
    <header class="page-head">
        <div>
            <h1>Účastníci</h1>
            <p class="lead">Lidé, které můžete přihlašovat do kurzů.</p>
        </div>
    </header>

    {{-- Počet účastníků se správným tvarem slova --}}
    <p class="count">{{ trans_choice('{0} Žádný účastník|{1} :count účastník|[2,4] :count účastníci|[5,*] :count účastníků', $participants->count()) }}</p>

    @if ($participants->isEmpty())
        {{-- Prázdný stav --}}
        <div class="empty">
            <p>Zatím tu nejsou žádní účastníci.</p>
            <a class="btn btn-primary" href="{{ route('participants.create') }}">Nový účastník</a>
        </div>
    @else
        {{-- Tabulka v panelu --}}
        <div class="panel">
            <table class="table">
                <thead>
                    <tr>
                        <th>Jméno</th>
                        <th>Kurzy</th>
                        {{-- Sloupec s tlačítky nemá viditelný nadpis, jen pro čtečky (sr-only) --}}
                        <th><span class="sr-only">Akce</span></th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Jeden řádek = jeden účastník --}}
                    @foreach ($participants as $participant)
                        <tr>
                            {{-- Jméno a e-mail --}}
                            <td>
                                <span class="name">{{ $participant->name }}</span>
                                <span class="sub">{{ $participant->email }}</span>
                            </td>

                            {{-- Štítky kurzů. @forelse = @foreach, který má větev @empty pro prázdný seznam. --}}
                            <td>
                                <div class="chips">
                                    @forelse ($participant->enrollments as $enrollment)
                                        {{-- Štítek = odkaz na detail kurzu. Třída chip-enrolled / chip-completed / chip-cancelled
                                             určí barvu, title = bublina s názvem kurzu a stavem po najetí myší. --}}
                                        <a class="chip chip-{{ $enrollment->status }}" href="{{ route('courses.show', $enrollment->course_id) }}" title="{{ $enrollment->course->name }}: {{ $enrollment->label() }}">{{ $enrollment->course->code }}</a>
                                    @empty
                                        <span class="muted">Bez přihlášky</span>
                                    @endforelse
                                </div>
                            </td>

                            {{-- Upravit / Smazat --}}
                            <td>
                                <div class="actions">
                                    <a class="btn btn-ghost btn-sm" href="{{ route('participants.edit', $participant) }}">Upravit</a>
                                    {{-- Smazání s potvrzením (POST + _method=DELETE) --}}
                                    <form method="POST" action="{{ route('participants.destroy', $participant) }}" onsubmit="return confirm('Opravdu smazat účastníka i s jeho přihláškami?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger btn-sm" type="submit">Smazat</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
