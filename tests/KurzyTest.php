<?php

// =====================================================================
// tests/KurzyTest.php = AUTOMATICKÁ KONTROLA aplikace.
// Spuštění: php artisan test
// Každá metoda test_... je jeden test. Test "jako prohlížeč" pošle
// požadavek ($this->post / $this->get / $this->patch) a pak ověří
// (assert...), co se stalo v odpovědi a v databázi.
// Databáze je v paměti (phpunit.xml) a RefreshDatabase ji před každým
// testem vytvoří znovu prázdnou, takže testy neovlivní skutečná data.
// =====================================================================

namespace Tests; // třída leží přímo ve složce tests

use App\Models\Course;                                    // model kurzu
use App\Models\Enrollment;                                // model přihlášky
use App\Models\Participant;                               // model účastníka
use Illuminate\Foundation\Testing\RefreshDatabase;        // čistá databáze pro každý test
use Illuminate\Foundation\Testing\TestCase;               // základní třída testů Laravelu (spustí aplikaci)

class KurzyTest extends TestCase
{
    // Před každým testem: migrace do prázdné databáze v paměti
    use RefreshDatabase;

    // -----------------------------------------------------------------
    // POVINNÁ KONTROLA ZE ZADÁNÍ: vytvoří kurz a ověří jeho uložení
    // -----------------------------------------------------------------
    public function test_vytvori_kurz_a_overi_jeho_ulozeni(): void
    {
        // Odešleme formulář "Nový kurz" stejně, jako by to udělal prohlížeč
        $response = $this->post('/courses', [
            'name' => 'Základy programování v Pythonu',
            'code' => 'py-101',                // malými písmeny, aplikace ho převede na velká
            'capacity' => 12,
            'starts_at' => '2026-12-01T17:00', // formát z pole datetime-local
        ]);

        // V databázi musí být přesně jeden kurz
        $this->assertSame(1, Course::count());

        // Najdeme ho a ověříme, že nás aplikace přesměrovala na jeho detail
        $course = Course::first();
        $response->assertRedirect(route('courses.show', $course));

        // Ověříme uložené hodnoty přímo v tabulce courses
        $this->assertDatabaseHas('courses', [
            'name' => 'Základy programování v Pythonu',
            'code' => 'PY-101',
            'capacity' => 12,
        ]);

        // Termín se uložil správně (datum i čas)
        $this->assertSame('2026-12-01 17:00', $course->starts_at->format('Y-m-d H:i'));

        // A kurz jde zobrazit: detail vrátí 200 OK a je v něm název kurzu
        $this->get(route('courses.show', $course))->assertOk()->assertSee('Základy programování v Pythonu');
    }

    // -----------------------------------------------------------------
    // Validace: kód kurzu je jedinečný
    // -----------------------------------------------------------------
    public function test_kod_kurzu_musi_byt_jedinecny(): void
    {
        // V databázi už je kurz s kódem WEB-201
        $this->kurz(['code' => 'WEB-201']);

        // Druhý kurz se stejným kódem (i malými písmeny) musí skončit chybou u pole code
        $this->post('/courses', $this->platnyKurz(['code' => 'web-201']))->assertSessionHasErrors('code');

        // Nový kurz nevznikl
        $this->assertSame(1, Course::count());
    }

    // -----------------------------------------------------------------
    // Validace: kapacita je celé číslo větší než 0
    // -----------------------------------------------------------------
    public function test_kapacita_musi_byt_cele_cislo_vetsi_nez_nula(): void
    {
        // Všechny tyto hodnoty jsou špatně: nula, záporné číslo, desetinné číslo, text, nic
        foreach ([0, -3, 2.5, 'deset', ''] as $spatnaKapacita) {
            $this->post('/courses', $this->platnyKurz(['capacity' => $spatnaKapacita]))
                ->assertSessionHasErrors('capacity');
        }

        // Žádný kurz se neuložil
        $this->assertSame(0, Course::count());
    }

    // -----------------------------------------------------------------
    // Validace: e-mail účastníka je platný a jedinečný
    // -----------------------------------------------------------------
    public function test_email_ucastnika_musi_byt_platny_a_jedinecny(): void
    {
        // Neplatný e-mail → chyba
        $this->post('/participants', ['name' => 'Jana Nováková', 'email' => 'tohle-neni-email'])
            ->assertSessionHasErrors('email');

        // Platný e-mail → bez chyby, účastník se uloží
        $this->post('/participants', ['name' => 'Jana Nováková', 'email' => 'jana@example.com'])
            ->assertSessionHasNoErrors();

        // Stejný e-mail podruhé (i s velkými písmeny) → chyba
        $this->post('/participants', ['name' => 'Jana Druhá', 'email' => 'JANA@example.com'])
            ->assertSessionHasErrors('email');

        // V databázi je jen jeden účastník
        $this->assertSame(1, Participant::count());
    }

    // -----------------------------------------------------------------
    // Validace: účastník se nesmí přihlásit dvakrát
    // -----------------------------------------------------------------
    public function test_ucastnik_se_nesmi_prihlasit_dvakrat(): void
    {
        $course = $this->kurz();
        $jana = $this->ucastnik('jana@example.com');

        // První přihlášení projde
        $this->post(route('courses.enroll', $course), ['participant_id' => $jana->id])
            ->assertSessionHasNoErrors();

        // Druhé přihlášení stejného účastníka skončí chybou
        $this->post(route('courses.enroll', $course), ['participant_id' => $jana->id])
            ->assertSessionHasErrors('participant_id');

        // Existuje jen jedna přihláška a má stav enrolled a vyplněný čas přihlášení
        $this->assertSame(1, Enrollment::count());
        $this->assertSame('enrolled', Enrollment::first()->status);
        $this->assertNotNull(Enrollment::first()->enrolled_at);
    }

    // -----------------------------------------------------------------
    // Domácí práce: hlídání kapacity (aktivní přihlášky nepřekročí kapacitu)
    // -----------------------------------------------------------------
    public function test_pocet_aktivnich_prihlasek_neprekroci_kapacitu(): void
    {
        // Kurz pro 2 lidi a 3 zájemci
        $course = $this->kurz(['capacity' => 2]);
        $a = $this->ucastnik('a@example.com');
        $b = $this->ucastnik('b@example.com');
        $c = $this->ucastnik('c@example.com');

        // Dva se vejdou
        $this->post(route('courses.enroll', $course), ['participant_id' => $a->id])->assertSessionHasNoErrors();
        $this->post(route('courses.enroll', $course), ['participant_id' => $b->id])->assertSessionHasNoErrors();

        // Třetí už ne: kurz je plný
        $this->post(route('courses.enroll', $course), ['participant_id' => $c->id])->assertSessionHasErrors('participant_id');
        $this->assertSame(2, Enrollment::count());

        // Účastník A přihlášku zruší → místo se uvolní
        $prihlaskaA = Enrollment::where('participant_id', $a->id)->first();
        $this->patch(route('enrollments.status', $prihlaskaA), ['status' => 'cancelled'])->assertSessionHasNoErrors();

        // Teď se C vejde
        $this->post(route('courses.enroll', $course), ['participant_id' => $c->id])->assertSessionHasNoErrors();
        $this->assertSame(2, $course->fresh()->activeCount());

        // Obnovení zrušené přihlášky A by kapacitu překročilo → chyba a stav zůstane cancelled
        $this->patch(route('enrollments.status', $prihlaskaA), ['status' => 'enrolled'])->assertSessionHasErrors('status');
        $this->assertSame('cancelled', $prihlaskaA->fresh()->status);
    }

    // -----------------------------------------------------------------
    // Domácí práce: stavy enrolled, cancelled a completed
    // -----------------------------------------------------------------
    public function test_prihlaska_ma_stavy_enrolled_cancelled_completed(): void
    {
        $course = $this->kurz();
        $jana = $this->ucastnik('jana@example.com');
        $this->post(route('courses.enroll', $course), ['participant_id' => $jana->id]);
        $prihlaska = Enrollment::first();

        // Projdeme všechny tři stavy a ověříme, že se uloží
        foreach (['completed', 'cancelled', 'enrolled'] as $stav) {
            $this->patch(route('enrollments.status', $prihlaska), ['status' => $stav])->assertSessionHasNoErrors();
            $this->assertSame($stav, $prihlaska->fresh()->status);
        }

        // Neznámý stav aplikace odmítne
        $this->patch(route('enrollments.status', $prihlaska), ['status' => 'smazano'])->assertSessionHasErrors('status');

        // Zrušená přihláška místo nezabírá, dokončená ano
        $prihlaska->update(['status' => 'cancelled']);
        $this->assertSame(0, $course->fresh()->activeCount());
        $prihlaska->update(['status' => 'completed']);
        $this->assertSame(1, $course->fresh()->activeCount());
    }

    // -----------------------------------------------------------------
    // Úprava kurzu: kapacitu nejde snížit pod počet aktivních přihlášek
    // -----------------------------------------------------------------
    public function test_kapacitu_nelze_snizit_pod_pocet_aktivnich_prihlasek(): void
    {
        $course = $this->kurz(['capacity' => 5]);
        foreach (['a', 'b', 'c'] as $jmeno) {
            $course->enrollments()->create([
                'participant_id' => $this->ucastnik($jmeno.'@example.com')->id,
                'enrolled_at' => now(),
                'status' => 'enrolled',
            ]);
        }

        // 3 aktivní přihlášky → kapacita 2 neprojde, kapacita 3 ano
        $this->put(route('courses.update', $course), $this->platnyKurz(['capacity' => 2]))->assertSessionHasErrors('capacity');
        $this->put(route('courses.update', $course), $this->platnyKurz(['capacity' => 3]))->assertSessionHasNoErrors();
        $this->assertSame(3, $course->fresh()->capacity);
    }

    // -----------------------------------------------------------------
    // Filtr kurzů podle termínu a volných míst + zobrazení obsazenosti
    // -----------------------------------------------------------------
    public function test_filtr_kurzu_podle_terminu_a_volnych_mist(): void
    {
        // Plný kurz v prosinci, volný kurz v prosinci, volný kurz v lednu
        $plny = $this->kurz(['name' => 'Plný prosincový', 'code' => 'A-1', 'capacity' => 1, 'starts_at' => '2026-12-05 10:00']);
        $this->kurz(['name' => 'Volný prosincový', 'code' => 'B-1', 'capacity' => 4, 'starts_at' => '2026-12-10 10:00']);
        $this->kurz(['name' => 'Volný lednový', 'code' => 'C-1', 'capacity' => 4, 'starts_at' => '2027-01-15 10:00']);
        $plny->enrollments()->create(['participant_id' => $this->ucastnik('x@example.com')->id, 'enrolled_at' => now(), 'status' => 'enrolled']);

        // Bez filtru: všechny tři kurzy a u plného obsazenost 1 / 1
        $this->get('/courses')->assertOk()
            ->assertSee('Plný prosincový')->assertSee('Volný prosincový')->assertSee('Volný lednový')
            ->assertSee('Plno');

        // Jen s volnými místy: plný kurz zmizí
        $this->get('/courses?free=1')->assertOk()
            ->assertDontSee('Plný prosincový')->assertSee('Volný prosincový')->assertSee('Volný lednový');

        // Termín od 1. 12. do 31. 12.: lednový kurz zmizí
        $this->get('/courses?from=2026-12-01&to=2026-12-31')->assertOk()
            ->assertSee('Plný prosincový')->assertSee('Volný prosincový')->assertDontSee('Volný lednový');

        // Obojí najednou: zůstane jen volný prosincový
        $this->get('/courses?from=2026-12-01&to=2026-12-31&free=1')->assertOk()
            ->assertDontSee('Plný prosincový')->assertSee('Volný prosincový')->assertDontSee('Volný lednový');
    }

    // -----------------------------------------------------------------
    // CRUD: úprava a smazání kurzu i účastníka (smazání odstraní i přihlášky)
    // -----------------------------------------------------------------
    public function test_crud_kurzu_a_ucastniku(): void
    {
        $course = $this->kurz();
        $jana = $this->ucastnik('jana@example.com');
        $course->enrollments()->create(['participant_id' => $jana->id, 'enrolled_at' => now(), 'status' => 'enrolled']);

        // Všechny stránky se načtou
        $this->get('/courses/create')->assertOk();
        $this->get(route('courses.edit', $course))->assertOk();
        $this->get('/participants')->assertOk()->assertSee('jana@example.com');
        $this->get('/participants/create')->assertOk();
        $this->get(route('participants.edit', $jana))->assertOk();

        // Úprava účastníka
        $this->put(route('participants.update', $jana), ['name' => 'Jana Svobodová', 'email' => 'jana@example.com'])->assertSessionHasNoErrors();
        $this->assertSame('Jana Svobodová', $jana->fresh()->name);

        // Smazání účastníka smaže i jeho přihlášku
        $this->delete(route('participants.destroy', $jana))->assertRedirect(route('participants.index'));
        $this->assertSame(0, Enrollment::count());

        // Smazání kurzu
        $this->delete(route('courses.destroy', $course))->assertRedirect(route('courses.index'));
        $this->assertSame(0, Course::count());
    }

    // =================================================================
    // Pomocné metody (ať se v testech neopakuje stejný kód)
    // =================================================================

    // Data platného kurzu pro formulář; $zmeny přepíšou vybrané hodnoty
    private function platnyKurz(array $zmeny = []): array
    {
        return array_merge([
            'name' => 'Testovací kurz',
            'code' => 'TEST-1',
            'capacity' => 10,
            'starts_at' => '2026-12-01T17:00',
        ], $zmeny);
    }

    // Vytvoří kurz rovnou v databázi (bez formuláře)
    private function kurz(array $zmeny = []): Course
    {
        return Course::create(array_merge([
            'name' => 'Testovací kurz',
            'code' => 'TEST-1',
            'capacity' => 10,
            'starts_at' => '2026-12-01 17:00',
        ], $zmeny));
    }

    // Vytvoří účastníka s daným e-mailem
    private function ucastnik(string $email): Participant
    {
        return Participant::create(['name' => 'Účastník '.$email, 'email' => $email]);
    }
}
