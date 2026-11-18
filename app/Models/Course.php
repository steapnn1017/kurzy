<?php

// =====================================================================
// app/Models/Course.php = model KURZU (tabulka courses).
// Model je PHP třída, přes kterou čteme a ukládáme řádky tabulky
// (Eloquent ORM). Jeden objekt Course = jeden řádek = jeden kurz.
// Sloupce: id, name, code, capacity, starts_at, created_at, updated_at
//
// Je tu i logika OBSAZENOSTI (aktivní přihlášky, volná místa), protože
// ji potřebuje controller (hlídání kapacity) i šablony (ukazatel).
// =====================================================================

namespace App\Models; // "adresa" třídy; podle ní ji Composer najde ve složce app/Models

use Illuminate\Database\Eloquent\Model;              // základní třída všech modelů
use Illuminate\Database\Eloquent\Relations\HasMany;  // typ vztahu "má mnoho" (1:N)

class Course extends Model
{
    // $fillable = sloupce, které smíme vyplnit najednou přes Course::create([...])
    // nebo $course->update([...]). Ochrana: z formuláře nejde podstrčit třeba "id".
    protected $fillable = ['name', 'code', 'capacity', 'starts_at'];

    // casts() = jak převést hodnoty z databáze na PHP typy
    protected function casts(): array
    {
        return [
            'capacity' => 'integer',   // kapacita bude vždy int (celé číslo)
            'starts_at' => 'datetime', // termín bude objekt Carbon → umí ->format(), ->isPast() atd.
        ];
    }

    // Vztah: jeden kurz má MNOHO přihlášek (1:N).
    // $course->enrollments   = kolekce přihlášek (načte se z DB při prvním použití)
    // $course->enrollments() = dotaz, na který jde navázat (->create(), ->where()...)
    public function enrollments(): HasMany
    {
        // Laravel podle názvu modelu sám pozná, že v tabulce enrollments hledá sloupec course_id
        return $this->hasMany(Enrollment::class);
    }

    // Počet AKTIVNÍCH přihlášek = ty, které zabírají místo (enrolled a completed).
    // Zrušená přihláška (cancelled) místo uvolní, proto se nepočítá.
    public function activeCount(): int
    {
        return $this->enrollments                      // všechny přihlášky kurzu (kolekce)
            ->whereIn('status', Enrollment::ACTIVE)    // necháme jen ty s aktivním stavem
            ->count();                                 // a spočítáme je
    }

    // Kolik míst ještě zbývá. max(0, ...) zajistí, že nikdy nevyjde záporné číslo.
    public function freeSeats(): int
    {
        return max(0, $this->capacity - $this->activeCount());
    }

    // Je kurz plný? Vrací true/false. Používá ho CourseController (hlídání kapacity)
    // i šablony (štítek "Plno", skrytí formuláře pro přihlášení).
    public function isFull(): bool
    {
        return $this->freeSeats() === 0;
    }

    // Obsazenost v procentech 0 až 100 (podle ní se kreslí délka ukazatele).
    // Dělení nulou nehrozí, kapacita je díky validaci vždy aspoň 1.
    public function occupancy(): int
    {
        return (int) min(100, round($this->activeCount() / $this->capacity * 100));
    }

    // Úroveň obsazenosti pro barvu ukazatele v šablonách:
    // full = plno (červená), high = od 75 % (žlutá), ok = jinak (světlá)
    public function level(): string
    {
        // Kurz je plný → červená
        if ($this->isFull()) {
            return 'full';
        }

        // Obsazeno aspoň ze 75 % → žlutá
        if ($this->occupancy() >= 75) {
            return 'high';
        }

        // Jinak je místa dost → světlá
        return 'ok';
    }
}
