<?php

// =====================================================================
// app/Http/Controllers/CourseController.php = všechno kolem KURZŮ.
//
// Controller = třída, která přijme požadavek z prohlížeče, pracuje
// s databází přes modely a vrátí stránku (view) nebo přesměrování.
//
// Obsahuje dvě části:
//   1) CRUD kurzů: seznam s filtrem, detail, vytvoření, úprava, smazání
//   2) PŘIHLÁŠKY (domácí práce): přihlášení účastníka do kurzu,
//      hlídání kapacity a změna stavu přihlášky
//
// Adresy, které sem vedou (jsou v routes/web.php):
//   GET    /courses                   index()         seznam + filtr
//   GET    /courses/create            create()        formulář nového kurzu
//   POST   /courses                   store()         uložení nového kurzu
//   GET    /courses/{course}          show()          detail kurzu
//   GET    /courses/{course}/edit     edit()          formulář úpravy
//   PUT    /courses/{course}          update()        uložení změn
//   DELETE /courses/{course}          destroy()       smazání kurzu
//   POST   /courses/{course}/enroll   enroll()        přihlášení účastníka
//   PATCH  /enrollments/{enrollment}  changeStatus()  změna stavu přihlášky
// =====================================================================

namespace App\Http\Controllers; // "adresa" třídy = složka app/Http/Controllers

use App\Models\Course;           // model kurzu (tabulka courses)
use App\Models\Enrollment;       // model přihlášky (tabulka enrollments)
use App\Models\Participant;      // model účastníka (tabulka participants)
use Illuminate\Http\Request;     // objekt s daty požadavku (formulář, parametry v adrese)
use Illuminate\Validation\Rule;  // pomocník pro pravidla unique (jedinečnost) a in (výčet hodnot)

class CourseController
{
    // České chybové hlášky pro všechna pravidla v tomto souboru.
    // Klíč "required" platí pro všechna pole, klíč "code.unique" jen pro pole code.
    // :max Laravel nahradí číslem z pravidla (např. max:255 → 255).
    // private const = konstanta jen pro tuto třídu, používá se jako self::MESSAGES.
    private const MESSAGES = [
        'required' => 'Toto pole je povinné.',                                  // prázdné povinné pole
        'max' => 'Text může mít nejvýše :max znaků.',                           // moc dlouhý text
        'date' => 'Zadejte platné datum.',                                      // nesmyslné datum
        'code.unique' => 'Kurz s tímto kódem už existuje.',                     // kód kurzu je jedinečný
        'capacity.integer' => 'Kapacita musí být celé číslo.',                  // kapacita je celé číslo...
        'capacity.min' => 'Kapacita musí být větší než 0.',                     // ...větší než 0
        'participant_id.required' => 'Vyberte účastníka.',                      // nevybraný účastník
        'participant_id.exists' => 'Vybraný účastník neexistuje.',              // účastník mezitím smazaný
        'participant_id.unique' => 'Tento účastník už je do kurzu přihlášený.', // nesmí se přihlásit dvakrát
        'status.in' => 'Neznámý stav přihlášky.',                               // stav mimo enrolled/completed/cancelled
    ];

    // =================================================================
    // 1) CRUD KURZŮ
    // =================================================================

    // SEZNAM KURZŮ + FILTR. Filtr přijde v adrese: /courses?from=2026-11-01&to=2026-12-31&free=1
    public function index(Request $request)
    {
        // Začneme skládat dotaz do databáze: všechny kurzy i s jejich přihláškami
        // (with = přihlášky se načtou najednou, potřebujeme je pro obsazenost),
        // seřazené podle termínu od nejdřívějšího
        $query = Course::with('enrollments')->orderBy('starts_at');

        // Filtr "Termín od": filled() = parametr v adrese existuje a není prázdný
        if ($request->filled('from')) {
            // whereDate = porovná jen datum (bez času): začátek kurzu >= zadané datum
            $query->whereDate('starts_at', '>=', $request->input('from'));
        }

        // Filtr "Termín do": začátek kurzu <= zadané datum
        if ($request->filled('to')) {
            $query->whereDate('starts_at', '<=', $request->input('to'));
        }

        // Až teď se dotaz opravdu pošle do databáze, get() vrátí kolekci kurzů
        $courses = $query->get();

        // Filtr "Jen s volnými místy": boolean('free') = true, když je zaškrtnuto.
        // Volná místa počítá model z načtených přihlášek, proto filtrujeme hotovou kolekci.
        if ($request->boolean('free')) {
            // filter() nechá jen kurzy, u kterých funkce vrátí true (víc než 0 volných míst)
            $courses = $courses->filter(fn ($course) => $course->freeSeats() > 0);
        }

        // Vykreslíme resources/views/courses.blade.php a pošleme jí proměnnou $courses
        return view('courses', ['courses' => $courses]);
    }

    // FORMULÁŘ NOVÉHO KURZU. Posíláme prázdný kurz (new Course), aby šablona
    // course-form.blade.php fungovala pro "nový" i "upravit" stejně.
    public function create()
    {
        return view('course-form', ['course' => new Course]);
    }

    // ULOŽENÍ NOVÉHO KURZU (formulář poslal data metodou POST)
    public function store(Request $request)
    {
        // Zkontrolujeme data (pravidla jsou dole ve validateCourse). Když něco nesedí,
        // Laravel sám vrátí uživatele na formulář i s chybami a vyplněnými hodnotami.
        $data = $this->validateCourse($request, new Course);

        // Vytvoříme nový řádek v tabulce courses
        $course = Course::create($data);

        // Přesměrujeme na detail nového kurzu. with('ok', ...) = jednorázová zpráva,
        // kterou layout ukáže jako vyskakovací hlášku (toast).
        return redirect()->route('courses.show', $course)->with('ok', 'Kurz byl vytvořen.');
    }

    // DETAIL KURZU. Laravel podle čísla v adrese (/courses/5) sám najde kurz
    // v databázi a předá ho jako $course. Když kurz neexistuje, vrátí chybu 404.
    public function show(Course $course)
    {
        // Načteme přihlášky kurzu i s jejich účastníky, seřazené podle času přihlášení.
        // fn ($query) => ... = malá funkce, která dotaz na přihlášky upraví.
        $course->load(['enrollments' => fn ($query) => $query->with('participant')->orderBy('enrolled_at')]);

        // Do výběru "Přihlásit účastníka" dáme jen ty, kdo v kurzu ještě přihlášku nemají:
        // pluck('participant_id') = seznam id už přihlášených, whereNotIn je vyřadí
        $participants = Participant::whereNotIn('id', $course->enrollments->pluck('participant_id'))
            ->orderBy('name')   // seřadit podle jména
            ->get();            // načíst z databáze

        // Vykreslíme course.blade.php s kurzem a nabídkou účastníků
        return view('course', ['course' => $course, 'participants' => $participants]);
    }

    // FORMULÁŘ ÚPRAVY KURZU (stejná šablona jako pro nový kurz)
    public function edit(Course $course)
    {
        return view('course-form', ['course' => $course]);
    }

    // ULOŽENÍ ZMĚN KURZU
    public function update(Request $request, Course $course)
    {
        // Zkontrolujeme data a rovnou je zapíšeme do řádku kurzu
        $course->update($this->validateCourse($request, $course));

        // Zpět na detail kurzu s hláškou
        return redirect()->route('courses.show', $course)->with('ok', 'Změny byly uloženy.');
    }

    // SMAZÁNÍ KURZU
    public function destroy(Course $course)
    {
        // Smaže řádek kurzu. Jeho přihlášky smaže databáze sama (cascadeOnDelete v migraci).
        $course->delete();

        // Zpět na seznam kurzů s hláškou
        return redirect()->route('courses.index')->with('ok', 'Kurz byl smazán.');
    }

    // KONTROLA DAT KURZU, společná pro vytvoření i úpravu (ať se pravidla neopakují).
    // $course = upravovaný kurz, u nového kurzu prázdný (new Course).
    private function validateCourse(Request $request, Course $course): array
    {
        // Kód převedeme na VELKÁ písmena (mb_ = funguje i s diakritikou),
        // aby "py-101" a "PY-101" byl stejný kód a kontrola jedinečnosti ho chytila
        $request->merge(['code' => mb_strtoupper((string) $request->input('code'))]);

        // validate(pravidla, hlášky) zkontroluje data. Při chybě vrátí uživatele
        // na formulář, jinak vrátí pole jen s ověřenými hodnotami.
        return $request->validate([
            // Název: povinný, nejvýš 255 znaků
            'name' => ['required', 'max:255'],

            // Kód: povinný, nejvýš 20 znaků a JEDINEČNÝ v tabulce courses.
            // ignore($course) = při úpravě nevadí, že tenhle kód má kurz sám.
            'code' => ['required', 'max:20', Rule::unique('courses')->ignore($course)],

            // Kapacita: bail = po první chybě už dál nekontroluj,
            // integer = celé číslo, min:1 = větší než 0
            'capacity' => ['bail', 'required', 'integer', 'min:1',
                // Vlastní pravidlo (funkce): kapacitu nejde snížit pod počet aktivních
                // přihlášek, jinak by kurz měl víc lidí, než se do něj vejde.
                // $value = zadaná kapacita, $fail = funkce, kterou ohlásíme chybu.
                function ($attribute, $value, $fail) use ($course) {
                    // $course->exists = kurz už je v databázi (jde o úpravu, ne o nový kurz)
                    if ($course->exists && $value < $course->activeCount()) {
                        $fail('Kapacitu nelze snížit pod počet aktivních přihlášek ('.$course->activeCount().').');
                    }
                },
            ],

            // Začátek kurzu: povinný a musí to být platné datum a čas
            'starts_at' => ['required', 'date'],
        ], self::MESSAGES);
    }

    // =================================================================
    // 2) PŘIHLÁŠKY (domácí práce)
    // =================================================================

    // PŘIHLÁŠENÍ ÚČASTNÍKA DO KURZU (formulář na detailu kurzu)
    public function enroll(Request $request, Course $course)
    {
        // Krok 1: kontrola vybraného účastníka
        $data = $request->validate([
            'participant_id' => [
                'bail',                    // po první chybě už dál nekontroluj
                'required',                // účastník musí být vybraný
                'exists:participants,id',  // a musí existovat v tabulce participants
                // ÚČASTNÍK SE NESMÍ PŘIHLÁSIT DVAKRÁT: v tabulce enrollments nesmí být
                // řádek se stejným participant_id A ZÁROVEŇ course_id tohoto kurzu
                Rule::unique('enrollments')->where('course_id', $course->id),
            ],
        ], self::MESSAGES);

        // Krok 2: HLÍDÁNÍ KAPACITY. Počet aktivních přihlášek nesmí překročit kapacitu.
        // isFull() je v modelu Course: true, když už nezbývá žádné volné místo.
        if ($course->isFull()) {
            // back() = zpět na stránku, odkud formulář přišel; withErrors = chyba pod polem
            return back()->withErrors(['participant_id' => 'Kurz je plně obsazený.']);
        }

        // Krok 3: vše v pořádku, vytvoříme přihlášku.
        // Přes $course->enrollments() se course_id doplní samo.
        $course->enrollments()->create([
            'participant_id' => $data['participant_id'],  // koho přihlašujeme
            'enrolled_at' => now(),                       // kdy: teď (české časové pásmo z config/app.php)
            'status' => 'enrolled',                       // nová přihláška je vždy "přihlášeno"
        ]);

        // Zpět na detail kurzu s hláškou
        return back()->with('ok', 'Účastník byl přihlášen.');
    }

    // ZMĚNA STAVU PŘIHLÁŠKY (přepínač Přihlášeno / Dokončeno / Zrušeno na detailu kurzu)
    public function changeStatus(Request $request, Enrollment $enrollment)
    {
        // Stav musí být jeden z povolených: array_keys vrátí ['enrolled', 'completed', 'cancelled']
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Enrollment::STATUSES))],
        ], self::MESSAGES);

        // Když ZRUŠENOU přihlášku znovu aktivujeme (cancelled → enrolled/completed),
        // zabere zase místo. Proto i tady hlídáme, aby kurz nebyl plný.
        $takesSeatAgain = ! $enrollment->isActive() && in_array($data['status'], Enrollment::ACTIVE);
        if ($takesSeatAgain && $enrollment->course->isFull()) {
            return back()->withErrors(['status' => 'Kurz je plně obsazený, zrušenou přihlášku teď nejde obnovit.']);
        }

        // Uložíme nový stav do databáze
        $enrollment->update($data);

        // Zpět na detail kurzu s hláškou
        return back()->with('ok', 'Stav přihlášky byl změněn.');
    }
}
