{{-- =====================================================================
     resources/views/courses.blade.php = SEZNAM KURZŮ (adresa /courses).
     Data posílá CourseController@index v proměnné $courses.
     Obsahuje: nadpis, filtr (termín od / do, jen volná místa) a karty
     kurzů s obsazeností.
     ===================================================================== --}}

{{-- Stránku "obalí" společný layout (resources/views/layout.blade.php) --}}
@extends('layout')

{{-- Text do titulku záložky prohlížeče --}}
@section('title', 'Kurzy')

{{-- Vše až po @endsection se vloží do layoutu na místo @yield('content') --}}
@section('content')
    {{-- Hlavička stránky --}}
    <header class="page-head">
        <div>
            <h1>Kurzy</h1>
            <p class="lead">Nabídka kurzů, jejich termíny a obsazenost.</p>
        </div>
    </header>

    {{-- FILTR: formulář s metodou GET. Hodnoty se po odeslání objeví v adrese
         (/courses?from=...&to=...&free=1) a controller podle nich omezí výběr. --}}
    <form class="filters" method="GET" action="{{ route('courses.index') }}">
        {{-- Termín od --}}
        <div class="field">
            {{-- for="from" propojí popisek s polem (klik na popisek = klik do pole) --}}
            <label for="from">Termín od</label>
            {{-- request('from') = hodnota z adresy, aby filtr zůstal vyplněný i po odeslání --}}
            <input class="input" type="date" id="from" name="from" value="{{ request('from') }}">
        </div>

        {{-- Termín do --}}
        <div class="field">
            <label for="to">Termín do</label>
            <input class="input" type="date" id="to" name="to" value="{{ request('to') }}">
        </div>

        {{-- Přepínač "Jen s volnými místy" (vzhled přepínače je v CSS, uvnitř je obyčejný checkbox).
             @checked(...) přidá atribut checked, když je v adrese free=1. --}}
        <label class="switch">
            <input type="checkbox" name="free" value="1" @checked(request()->boolean('free'))>
            <span class="switch-track" aria-hidden="true"></span>
            Jen s volnými místy
        </label>

        {{-- Tlačítka filtru --}}
        <div class="filters-actions">
            <button class="btn btn-primary" type="submit">Filtrovat</button>
            {{-- "Zrušit" se ukáže jen tehdy, když je nějaký filtr vyplněný --}}
            @if (request()->anyFilled(['from', 'to', 'free']))
                <a class="btn btn-ghost" href="{{ route('courses.index') }}">Zrušit</a>
            @endif
        </div>
    </form>

    {{-- Počet nalezených kurzů se správným českým tvarem slova.
         trans_choice vybere část textu podle čísla: {0} nula, {1} jedna, [2,4] dva až čtyři, [5,*] pět a víc. --}}
    <p class="count">{{ trans_choice('{0} Žádný kurz|{1} :count kurz|[2,4] :count kurzy|[5,*] :count kurzů', $courses->count()) }}</p>

    {{-- Prázdný stav: žádný kurz --}}
    @if ($courses->isEmpty())
        <div class="empty">
            {{-- Rozlišíme, jestli kurzy nejsou vůbec, nebo jen neodpovídají filtru --}}
            @if (request()->anyFilled(['from', 'to', 'free']))
                <p>Filtru neodpovídá žádný kurz.</p>
                <a class="btn btn-ghost" href="{{ route('courses.index') }}">Zrušit filtr</a>
            @else
                <p>Zatím tu nejsou žádné kurzy.</p>
                <a class="btn btn-primary" href="{{ route('courses.create') }}">Nový kurz</a>
            @endif
        </div>
    @else
        {{-- Mřížka karet. Každá karta je odkaz na detail kurzu. --}}
        <div class="grid">
            {{-- @foreach projde všechny kurzy, $loop->index je pořadí (0, 1, 2...) --}}
            @foreach ($courses as $course)
                {{-- --i = pořadí karty, CSS podle něj zpozdí animaci (karty naskakují postupně) --}}
                <a class="card" href="{{ route('courses.show', $course) }}" style="--i: {{ $loop->index }}">
                    {{-- Horní řádek: kód kurzu a štítek --}}
                    <div class="card-top">
                        <span class="code">{{ $course->code }}</span>

                        {{-- Štítek: proběhlý kurz / plno / počet volných míst --}}
                        @if ($course->starts_at->isPast())
                            <span class="chip">Proběhl</span>
                        @elseif ($course->isFull())
                            <span class="chip chip-full">Plno</span>
                        @else
                            <span class="chip chip-free">{{ trans_choice('{1} :count volné místo|[2,4] :count volná místa|[5,*] :count volných míst', $course->freeSeats()) }}</span>
                        @endif
                    </div>

                    {{-- Název kurzu. {{ }} vypíše text bezpečně (HTML znaky převede) --}}
                    <h2 class="card-title">{{ $course->name }}</h2>

                    {{-- Termín: format('j. n. Y, H:i') = např. 18. 11. 2026, 17:00 --}}
                    <p class="card-date">{{ $course->starts_at->format('j. n. Y, H:i') }}</p>

                    {{-- Ukazatel obsazenosti. --p = obsazenost 0 až 1 (délka výplně),
                         data-level = barva (ok / high / full), vše počítá model Course. --}}
                    <div class="meter" data-level="{{ $course->level() }}" style="--p: {{ $course->occupancy() / 100 }}" aria-hidden="true"><span></span></div>

                    {{-- Obsazenost čísly: aktivní přihlášky / kapacita --}}
                    <p class="meter-label">
                        <span><strong>{{ $course->activeCount() }}</strong> / {{ $course->capacity }} obsazeno</span>
                        <span>{{ $course->occupancy() }} %</span>
                    </p>
                </a>
            @endforeach
        </div>
    @endif
@endsection
