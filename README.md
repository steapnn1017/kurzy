# Kurzy a přihlašování účastníků

Laravel aplikace pro nabídku kurzů a přihlašování účastníků. Přihláška propojuje účastníka s kurzem a má vlastní stav (přihlášeno, dokončeno, zrušeno). Celé UI je česky a tmavé.

**Technologie:** PHP 8.3+, Laravel 13, SQLite (databáze je jeden soubor), šablony Blade a čisté CSS přímo v layoutu (žádné npm, Vite ani soubory písma).

## Spuštění

```bash
composer install        # stáhne Laravel a další knihovny do složky vendor/
composer setup          # vytvoří .env, klíč, databázi a naplní ji ukázkovými daty
php artisan serve --port=8020
```

Pak otevřete http://127.0.0.1:8020

`composer setup` jde spustit kdykoli znovu. Databázi smaže a naplní ukázkovými daty: 7 kurzů (jeden plný, jeden už proběhlý, jeden bez přihlášek) a 14 účastníků.

## Všechny soubory (27) a k čemu jsou

```
app/
  Http/Controllers/
    CourseController.php        kurzy (CRUD + filtr) a přihlášky (přihlášení, kapacita, stavy)
    ParticipantController.php   účastníci (CRUD)
  Models/
    Course.php                  kurz + výpočet obsazenosti a volných míst
    Participant.php             účastník
    Enrollment.php              přihláška + seznam stavů
bootstrap/
  app.php                       poskládání aplikace (kde jsou routy)
  cache/.gitignore              složka pro soubory, které si Laravel generuje sám
config/
  app.php                       jediné nastavení: české časové pásmo
database/
  migrations/…_create_kurzy_tables.php   všechny 3 tabulky v jednom souboru
  seeders/DatabaseSeeder.php             ukázková data
public/
  index.php                     vstupní bod pro prohlížeč
resources/views/
  layout.blade.php              společná kostra stránek + CELÉ CSS
  courses.blade.php             seznam kurzů s filtrem
  course.blade.php              detail kurzu: obsazenost, přihlášení, seznam přihlášených
  course-form.blade.php         formulář kurzu (nový i úprava)
  participants.blade.php        seznam účastníků
  participant-form.blade.php    formulář účastníka (nový i úprava)
routes/
  web.php                       všechny adresy aplikace
storage/framework/views/.gitignore   složka pro zkompilované šablony
tests/
  KurzyTest.php                 automatická kontrola
.env.example   vzor nastavení       .gitignore   co nedávat do gitu
artisan        příkazy v terminálu  phpunit.xml  nastavení testů
composer.json  seznam knihoven      composer.lock  přesné verze knihoven
README.md      tento návod
```

## Zadání → kde to je

| Zadání | Soubor |
|---|---|
| Course: name, code, capacity, starts_at | `app/Models/Course.php`, tabulka v migraci |
| Participant: name, email | `app/Models/Participant.php`, tabulka v migraci |
| Enrollment: course_id, participant_id, enrolled_at, status | `app/Models/Enrollment.php`, tabulka v migraci |
| CRUD kurzů | `CourseController` (část 1), šablony `courses`, `course`, `course-form` |
| CRUD účastníků | `ParticipantController`, šablony `participants`, `participant-form` |
| Přihlášení účastníka do kurzu | `CourseController@enroll`, formulář v `course.blade.php` |
| Obsazenost a seznam přihlášených | `activeCount()`, `freeSeats()`, `occupancy()` v `Course.php`, zobrazení v `course.blade.php` a na kartách v `courses.blade.php` |
| Filtr podle termínu a volných míst | `CourseController@index` (parametry `from`, `to`, `free` v adrese), formulář v `courses.blade.php` |
| **Domácí práce:** přihlášení a hlídání kapacity | `CourseController@enroll` a `@changeStatus` (část 2) |
| **Domácí práce:** stavy enrolled, cancelled, completed | `Enrollment::STATUSES` a `Enrollment::ACTIVE`, přepínač stavu v `course.blade.php`, `CourseController@changeStatus` |
| Automatická kontrola | `tests/KurzyTest.php` |

## Validační pravidla

Pravidla a jejich české hlášky jsou vždy pohromadě v controlleru (konstanta `MESSAGES` nahoře v souboru).

| Pravidlo | Jak je zajištěné |
|---|---|
| Kód kurzu je jedinečný | `Rule::unique('courses')` v `CourseController::validateCourse()` + `unique()` v databázi. Kód se převede na velká písmena, takže `py-101` a `PY-101` jsou stejný kód. |
| Kapacita je celé číslo větší než 0 | `integer` a `min:1` ve `validateCourse()` |
| E-mail je platný a jedinečný | `email` a `Rule::unique('participants')` v `ParticipantController::validateParticipant()` + `unique()` v databázi. E-mail se převede na malá písmena. |
| Účastník se nesmí přihlásit dvakrát | `Rule::unique('enrollments')->where('course_id', ...)` v `CourseController@enroll` + `unique(['course_id', 'participant_id'])` v databázi |
| Aktivní přihlášky nepřekročí kapacitu | `$course->isFull()` v `CourseController@enroll` (nové přihlášení) i `@changeStatus` (obnovení zrušené přihlášky). Navíc kapacitu kurzu nejde snížit pod počet aktivních přihlášek. |

Formuláře mají `novalidate`, aby kontrolu dělal server a české hlášky se ukázaly pod poli.

## Stavy přihlášky

| Stav v databázi | V aplikaci | Zabírá místo? |
|---|---|---|
| `enrolled` | Přihlášeno | ano |
| `completed` | Dokončeno | ano (účastník kurzem prošel) |
| `cancelled` | Zrušeno | **ne**, místo se uvolní |

„Aktivní přihláška“ = `enrolled` nebo `completed` (konstanta `Enrollment::ACTIVE`). Kdyby měl místo zabírat jen stav `enrolled`, stačí z konstanty smazat `'completed'`.

## Automatická kontrola (testy)

```bash
php artisan test
```

10 testů v `tests/KurzyTest.php`, běží v databázi v paměti (skutečná data nezmění):

| Test | Co ověřuje |
|---|---|
| `test_vytvori_kurz_a_overi_jeho_ulozeni` | **povinná kontrola ze zadání:** pošle formulář, ověří řádek v tabulce, termín a zobrazení detailu |
| `test_kod_kurzu_musi_byt_jedinecny` | druhý kurz se stejným kódem (i malými písmeny) neprojde |
| `test_kapacita_musi_byt_cele_cislo_vetsi_nez_nula` | 0, −3, 2,5, text i prázdná hodnota neprojdou |
| `test_email_ucastnika_musi_byt_platny_a_jedinecny` | neplatný ani duplicitní e-mail neprojde |
| `test_ucastnik_se_nesmi_prihlasit_dvakrat` | druhá přihláška stejného účastníka neprojde |
| `test_pocet_aktivnich_prihlasek_neprekroci_kapacitu` | plný kurz nepřijme dalšího, zrušení místo uvolní, obnovení v plném kurzu neprojde |
| `test_prihlaska_ma_stavy_enrolled_cancelled_completed` | všechny tři stavy se uloží, neznámý stav neprojde |
| `test_kapacitu_nelze_snizit_pod_pocet_aktivnich_prihlasek` | kapacitu nejde snížit pod počet aktivních přihlášek |
| `test_filtr_kurzu_podle_terminu_a_volnych_mist` | filtr podle termínu, podle volných míst i obou najednou |
| `test_crud_kurzu_a_ucastniku` | všechny stránky, úprava a smazání (smazání odstraní i přihlášky) |

## Jak putuje požadavek

1. Prohlížeč pošle požadavek, např. `POST /courses/1/enroll`.
2. `public/index.php` načte aplikaci (`bootstrap/app.php`).
3. `routes/web.php` najde adresu a zavolá `CourseController@enroll`.
4. Controller zkontroluje data, zkontroluje kapacitu přes model `Course` a uloží přihlášku.
5. Vrátí prohlížeč na detail kurzu s hláškou, kterou `layout.blade.php` ukáže jako toast.

## composer.json (JSON neumí komentáře, proto je vysvětlený tady)

| Část | Význam |
|---|---|
| `require` | knihovny nutné pro běh: PHP 8.3+ a Laravel 13 |
| `require-dev` | knihovny jen pro testy: PHPUnit (spouští testy), Collision (hezký výpis `php artisan test`), Mockery (Laravel ho při testech potřebuje) |
| `autoload` | kde Composer hledá třídy: `App\` = složka `app/`, `Database\Seeders\` = `database/seeders/` |
| `scripts.setup` | příkaz `composer setup`: zkopíruje `.env.example` na `.env`, vygeneruje klíč, vytvoří soubor databáze a spustí migrace se seedem |
| `scripts.post-autoload-dump` | po každém `composer install` Laravel znovu načte seznam balíčků |
| `config` | nastavení Composeru (rychlejší autoload, stahování hotových balíčků) |

## Proč je souborů tak málo

- Laravel 13 má výchozí nastavení ve frameworku, takže `config/` obsahuje jen časové pásmo.
- Kurzy i přihlášky obsluhuje jeden controller, hlášky jsou přímo u pravidel (žádná složka `lang/`).
- Všechny tabulky jsou v jedné migraci a celé CSS je v jednom layoutu.
- Formulář pro „nový“ i „upravit“ je vždy jedna šablona.
- Session se ukládá do zašifrované cookie a cache do paměti, takže nejsou potřeba složky pro sessions, cache ani logy (Laravel si je případně vytvoří sám).
- Písmo je systémové (na Macu SF Pro), žádné soubory písma.

## Známá omezení

- Aplikace nemá přihlašování správce, data může upravovat kdokoli, kdo ji otevře (zadání to nevyžaduje).
- Kdyby dva lidé přesně ve stejný okamžik obsadili poslední místo, teoreticky by mohli projít oba. Ve velkém provozu by se kontrola s uložením zabalila do databázové transakce se zámkem.
- Datumová pole ukazují formát podle jazyka prohlížeče.
