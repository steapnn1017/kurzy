<?php

// =====================================================================
// database/seeders/DatabaseSeeder.php = UKÁZKOVÁ DATA.
// Spustí se příkazem `php artisan migrate:fresh --seed`
// (je součástí `composer setup`). Vytvoří kurzy, účastníky a přihlášky,
// aby aplikace po spuštění nebyla prázdná.
// Termíny se počítají od dneška (now()), takže jsou pořád aktuální.
// =====================================================================

namespace Database\Seeders; // třída leží ve složce database/seeders

use App\Models\Course;           // model kurzu
use App\Models\Participant;      // model účastníka
use Illuminate\Database\Seeder;  // základní třída seederů

class DatabaseSeeder extends Seeder
{
    // run() se zavolá při seedování
    public function run(): void
    {
        // ---------- Kurzy: [název, kód, kapacita, za kolik dní začíná, hodina, minuta] ----------
        // Záporný počet dní = kurz už proběhl.
        $courses = [
            ['Základy programování v Pythonu', 'PY-101', 12, 6, 17, 0],
            ['Tvorba webových stránek', 'WEB-201', 10, 13, 16, 30],
            ['Excel pro každý den', 'XLS-110', 8, 3, 18, 0],
            ['Fotografie pro začátečníky', 'FOTO-01', 15, 20, 10, 0],
            ['Angličtina na cesty', 'ENG-A2', 14, 27, 17, 30],
            ['Základy grafického designu', 'GRA-101', 6, 41, 9, 0],
            ['První pomoc v praxi', 'ZDR-01', 20, -12, 9, 0],
        ];

        // Projdeme pole a každý kurz uložíme do databáze
        foreach ($courses as [$name, $code, $capacity, $days, $hour, $minute]) {
            Course::create([
                'name' => $name,
                'code' => $code,
                'capacity' => $capacity,
                // dnešek + počet dní, čas nastavíme na hodinu a minutu z pole
                'starts_at' => now()->addDays($days)->setTime($hour, $minute),
            ]);
        }

        // ---------- Účastníci: [jméno, e-mail] ----------
        // Domény example.com jsou rezervované pro ukázky, nikomu skutečnému nepatří.
        $people = [
            ['Jana Nováková', 'jana.novakova@example.com'],
            ['Petr Svoboda', 'petr.svoboda@example.com'],
            ['Lucie Dvořáková', 'lucie.dvorakova@example.com'],
            ['Tomáš Černý', 'tomas.cerny@example.com'],
            ['Kateřina Procházková', 'katerina.prochazkova@example.com'],
            ['Martin Kučera', 'martin.kucera@example.com'],
            ['Veronika Veselá', 'veronika.vesela@example.com'],
            ['Jakub Horák', 'jakub.horak@example.com'],
            ['Tereza Němcová', 'tereza.nemcova@example.com'],
            ['Ondřej Marek', 'ondrej.marek@example.com'],
            ['Barbora Pokorná', 'barbora.pokorna@example.com'],
            ['David Pospíšil', 'david.pospisil@example.com'],
            ['Eliška Králová', 'eliska.kralova@example.com'],
            ['Filip Jelínek', 'filip.jelinek@example.com'],
        ];

        // Uložíme účastníky a zapamatujeme si je v poli $participants (index 0, 1, 2...)
        $participants = [];
        foreach ($people as [$name, $email]) {
            $participants[] = Participant::create(['name' => $name, 'email' => $email]);
        }

        // ---------- Přihlášky: kód kurzu => [stav => čísla účastníků z pole výše] ----------
        $plan = [
            'PY-101' => ['enrolled' => [0, 1, 2, 3, 4, 5, 6], 'cancelled' => [7]],
            'WEB-201' => ['enrolled' => [8, 9, 10, 11]],
            'XLS-110' => ['enrolled' => [1, 3, 5, 7, 9, 11, 12, 13]],   // 8 z 8 = kurz je plný
            'FOTO-01' => ['enrolled' => [2, 6]],
            'ENG-A2' => ['enrolled' => [0, 4, 10], 'cancelled' => [13]],
            'ZDR-01' => ['completed' => [0, 2, 3, 6, 8, 9, 12], 'cancelled' => [5]], // proběhlý kurz
            // GRA-101 zatím nemá žádnou přihlášku (ukázka prázdného seznamu)
        ];

        // Projdeme plán: pro každý kurz a každý stav vytvoříme přihlášky
        foreach ($plan as $code => $byStatus) {
            // Najdeme kurz podle kódu
            $course = Course::where('code', $code)->first();

            // $status = 'enrolled' / 'completed' / 'cancelled', $indexes = čísla účastníků
            foreach ($byStatus as $status => $indexes) {
                foreach ($indexes as $i) {
                    $course->enrollments()->create([
                        'participant_id' => $participants[$i]->id,
                        // Přihlášení před 30 až 17 dny (vždy před začátkem kurzu)
                        'enrolled_at' => now()->subDays(30 - $i)->setTime(8 + $i, 15),
                        'status' => $status,
                    ]);
                }
            }
        }
    }
}
