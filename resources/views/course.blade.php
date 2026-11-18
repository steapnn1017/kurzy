{{-- =====================================================================
     resources/views/course.blade.php = DETAIL KURZU (adresa /courses/{id}).
     Data posílá CourseController@show:
       $course       = kurz i s přihláškami a jejich účastníky
       $participants = účastníci, kteří v kurzu ještě přihlášku nemají
     Obsahuje: obsazenost, formulář pro přihlášení účastníka a seznam
     přihlášených s přepínačem stavu (Přihlášeno / Dokončeno / Zrušeno).
     ===================================================================== --}}

{{-- Společný layout --}}
@extends('layout')

{{-- Titulek záložky = název kurzu --}}
@section('title', $course->name)

@section('content')
    {{-- Odkaz zpět na seznam --}}
    <a class="back" href="{{ route('courses.index') }}">← Všechny kurzy</a>

    {{-- Hlavička: kód, název, termín a tlačítka Upravit / Smazat --}}
    <header class="page-head">
        <div>
            <span class="code">{{ $course->code }}</span>
            <h1>{{ $course->name }}</h1>
            <p class="lead">Začátek {{ $course->starts_at->format('j. n. Y, H:i') }}</p>
        </div>

        <div class="head-actions">
            {{-- Úprava kurzu --}}
            <a class="btn btn-ghost" href="{{ route('courses.edit', $course) }}">Upravit</a>

            {{-- Smazání: prohlížeč umí jen GET a POST, proto POST + @method('DELETE').
                 onsubmit + confirm() = před smazáním se zeptá "Opravdu?" (Zrušit = nic se nepošle). --}}
            <form method="POST" action="{{ route('courses.destroy', $course) }}" onsubmit="return confirm('Opravdu smazat kurz i se všemi přihláškami?')">
                {{-- @csrf = skrytý token proti podvrženým formulářům z cizích webů --}}
                @csrf
                {{-- Skryté pole _method=DELETE, Laravel podle něj zavolá destroy() --}}
                @method('DELETE')
                <button class="btn btn-danger" type="submit">Smazat</button>
            </form>
        </div>
    </header>

    <div class="detail">
        {{-- ===== PRAVÝ SLOUPEC (na mobilu nahoře): obsazenost + přihlášení ===== --}}
        <aside class="side">
            {{-- Obsazenost --}}
            <section class="panel">
                <h2 class="panel-title">Obsazenost</h2>

                {{-- Velké číslo: aktivní přihlášky / kapacita --}}
                <p class="big">{{ $course->activeCount() }}<span> / {{ $course->capacity }}</span></p>

                {{-- Ukazatel (stejný jako na kartě v seznamu) --}}
                <div class="meter" data-level="{{ $course->level() }}" style="--p: {{ $course->occupancy() / 100 }}" aria-hidden="true"><span></span></div>

                {{-- Tři čísla vedle sebe. <dl> = seznam "pojem: hodnota" --}}
                <dl class="stats">
                    <div><dt>Kapacita</dt><dd>{{ $course->capacity }}</dd></div>
                    <div><dt>Obsazeno</dt><dd>{{ $course->activeCount() }}</dd></div>
                    <div><dt>Volná místa</dt><dd>{{ $course->freeSeats() }}</dd></div>
                </dl>
            </section>

            {{-- Přihlášení účastníka --}}
            <section class="panel">
                <h2 class="panel-title">Přihlásit účastníka</h2>

                @if ($course->isFull())
                    {{-- Plný kurz: formulář vůbec neukážeme (a server to stejně hlídá i sám) --}}
                    <p class="note note-red">Kurz je plně obsazený. Místo uvolní zrušení některé přihlášky.</p>
                @elseif ($participants->isEmpty())
                    {{-- Není koho přihlásit --}}
                    <p class="note">Všichni účastníci už v kurzu přihlášku mají. <a href="{{ route('participants.create') }}">Přidat účastníka</a></p>
                @else
                    {{-- Formulář: POST na /courses/{id}/enroll → CourseController@enroll.
                         novalidate = kontrolu dělá server, aby se ukázaly české hlášky. --}}
                    <form class="stack" method="POST" action="{{ route('courses.enroll', $course) }}" novalidate>
                        {{-- Ochranný token formuláře --}}
                        @csrf
                        <div class="field">
                            <label for="participant_id">Účastník</label>
                            {{-- @class přidá třídu invalid (červený rámeček), když má pole chybu --}}
                            <select @class(['input', 'invalid' => $errors->has('participant_id')]) id="participant_id" name="participant_id">
                                <option value="">Vyberte účastníka</option>
                                {{-- Každý účastník jako jedna možnost. @selected = předvybrat po chybě (old = minulá hodnota) --}}
                                @foreach ($participants as $participant)
                                    <option value="{{ $participant->id }}" @selected(old('participant_id') == $participant->id)>{{ $participant->name }} ({{ $participant->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="btn btn-primary btn-block" type="submit">Přihlásit do kurzu</button>
                    </form>
                @endif

                {{-- Chyba přihlášení (už přihlášený, plný kurz, nevybraný účastník).
                     @error vypíše blok jen při chybě, text je v proměnné $message. --}}
                @error('participant_id')
                    <p class="error">{{ $message }}</p>
                @enderror
            </section>
        </aside>

        {{-- ===== LEVÝ SLOUPEC: seznam přihlášených ===== --}}
        <section class="panel list">
            <div class="panel-head">
                <h2 class="panel-title">Přihlášení účastníci</h2>
                {{-- Počet všech přihlášek (i zrušených) se správným tvarem slova --}}
                <span class="muted">{{ trans_choice('{0} žádná přihláška|{1} :count přihláška|[2,4] :count přihlášky|[5,*] :count přihlášek', $course->enrollments->count()) }}</span>
            </div>

            {{-- Chyba při změně stavu (obnovení zrušené přihlášky v plném kurzu) --}}
            @error('status')
                <p class="error pad">{{ $message }}</p>
            @enderror

            @if ($course->enrollments->isEmpty())
                {{-- Prázdný seznam --}}
                <p class="empty-inline">Do kurzu se zatím nikdo nepřihlásil.</p>
            @else
                <table class="table">
                    {{-- Záhlaví tabulky --}}
                    <thead>
                        <tr>
                            <th>Účastník</th>
                            <th>Přihlášen</th>
                            <th>Stav</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Jeden řádek = jedna přihláška --}}
                        @foreach ($course->enrollments as $enrollment)
                            {{-- Zrušená přihláška dostane třídu is-cancelled (šedé jméno) --}}
                            <tr @class(['is-cancelled' => $enrollment->status === 'cancelled'])>
                                {{-- Jméno a e-mail účastníka (načtené přes vztah participant) --}}
                                <td>
                                    <span class="name">{{ $enrollment->participant->name }}</span>
                                    <span class="sub">{{ $enrollment->participant->email }}</span>
                                </td>

                                {{-- Kdy se přihlásil (enrolled_at) --}}
                                <td class="muted nowrap">{{ $enrollment->enrolled_at->format('j. n. Y, H:i') }}</td>

                                {{-- Přepínač stavu: jeden formulář, tři tlačítka. Klik na tlačítko pošle
                                     status=<hodnota tlačítka> na PATCH /enrollments/{id} → CourseController@changeStatus. --}}
                                <td>
                                    <form class="seg" method="POST" action="{{ route('enrollments.status', $enrollment) }}">
                                        @csrf
                                        @method('PATCH')
                                        {{-- Projdeme stavy z modelu: $value = enrolled / completed / cancelled, $label = český text --}}
                                        @foreach (\App\Models\Enrollment::STATUSES as $value => $label)
                                            {{-- Aktuální stav dostane třídu "on" (barevné podbarvení),
                                                 aria-pressed řekne čtečce obrazovky, který je zapnutý --}}
                                            <button type="submit" name="status" value="{{ $value }}"
                                                @class(['seg-'.$value, 'on' => $enrollment->status === $value])
                                                aria-pressed="{{ $enrollment->status === $value ? 'true' : 'false' }}">{{ $label }}</button>
                                        @endforeach
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    </div>
@endsection
