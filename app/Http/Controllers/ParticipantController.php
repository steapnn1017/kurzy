<?php

// =====================================================================
// app/Http/Controllers/ParticipantController.php = CRUD ÚČASTNÍKŮ.
//
// Adresy, které sem vedou (Route::resource v routes/web.php,
// bez detailu "show", účastníka stačí vidět v seznamu):
//   GET    /participants                     index()    seznam
//   GET    /participants/create              create()   formulář nového účastníka
//   POST   /participants                     store()    uložení nového účastníka
//   GET    /participants/{participant}/edit  edit()     formulář úpravy
//   PUT    /participants/{participant}       update()   uložení změn
//   DELETE /participants/{participant}       destroy()  smazání
// =====================================================================

namespace App\Http\Controllers; // třída leží ve složce app/Http/Controllers

use App\Models\Participant;      // model účastníka (tabulka participants)
use Illuminate\Http\Request;     // objekt s daty požadavku (formulář)
use Illuminate\Validation\Rule;  // pomocník pro pravidlo unique (jedinečnost)

class ParticipantController
{
    // České chybové hlášky. Klíč "email" = pravidlo email (platný formát),
    // klíč "email.unique" = pravidlo unique jen u pole email.
    private const MESSAGES = [
        'required' => 'Toto pole je povinné.',                          // prázdné povinné pole
        'max' => 'Text může mít nejvýše :max znaků.',                   // moc dlouhý text
        'email' => 'Zadejte platnou e-mailovou adresu.',                // e-mail je platný
        'email.unique' => 'Účastník s tímto e-mailem už existuje.',     // e-mail je jedinečný
    ];

    // SEZNAM ÚČASTNÍKŮ seřazený podle jména
    public function index()
    {
        // with('enrollments.course') = ke každému účastníkovi načti jeho přihlášky
        // a ke každé přihlášce její kurz (v seznamu ukazujeme štítky s kódy kurzů)
        $participants = Participant::with('enrollments.course')->orderBy('name')->get();

        // Vykreslíme resources/views/participants.blade.php
        return view('participants', ['participants' => $participants]);
    }

    // FORMULÁŘ NOVÉHO ÚČASTNÍKA (prázdný účastník, stejná šablona jako úprava)
    public function create()
    {
        return view('participant-form', ['participant' => new Participant]);
    }

    // ULOŽENÍ NOVÉHO ÚČASTNÍKA
    public function store(Request $request)
    {
        // Zkontrolujeme data (při chybě zpět na formulář) a vytvoříme řádek v tabulce
        Participant::create($this->validateParticipant($request, new Participant));

        // Zpět na seznam s hláškou
        return redirect()->route('participants.index')->with('ok', 'Účastník byl přidán.');
    }

    // FORMULÁŘ ÚPRAVY (Laravel najde účastníka podle čísla v adrese)
    public function edit(Participant $participant)
    {
        return view('participant-form', ['participant' => $participant]);
    }

    // ULOŽENÍ ZMĚN
    public function update(Request $request, Participant $participant)
    {
        // Zkontrolujeme data a zapíšeme je do řádku účastníka
        $participant->update($this->validateParticipant($request, $participant));

        // Zpět na seznam s hláškou
        return redirect()->route('participants.index')->with('ok', 'Změny byly uloženy.');
    }

    // SMAZÁNÍ ÚČASTNÍKA (jeho přihlášky smaže databáze sama díky cascadeOnDelete)
    public function destroy(Participant $participant)
    {
        $participant->delete();

        // Zpět na seznam s hláškou
        return redirect()->route('participants.index')->with('ok', 'Účastník byl smazán.');
    }

    // KONTROLA DAT ÚČASTNÍKA, společná pro vytvoření i úpravu
    private function validateParticipant(Request $request, Participant $participant): array
    {
        // E-mail převedeme na malá písmena, aby "Jana@Example.com" a "jana@example.com"
        // byl pro kontrolu jedinečnosti stejný e-mail
        $request->merge(['email' => mb_strtolower((string) $request->input('email'))]);

        // Kontrola pravidel s českými hláškami
        return $request->validate([
            // Jméno: povinné, nejvýš 255 znaků
            'name' => ['required', 'max:255'],
            // E-mail: povinný, PLATNÝ formát (email), nejvýš 255 znaků a JEDINEČNÝ.
            // ignore($participant) = při úpravě nevadí vlastní stávající e-mail.
            'email' => ['required', 'email', 'max:255', Rule::unique('participants')->ignore($participant)],
        ], self::MESSAGES);
    }
}
