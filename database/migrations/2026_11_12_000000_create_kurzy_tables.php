<?php

// =====================================================================
// MIGRACE = PHP popis databázových tabulek.
// `php artisan migrate` (součást `composer setup`) podle ní tabulky
// vytvoří. Všechny tři tabulky jsou v jednom souboru, ať je to přehledné.
//
//   courses       = kurzy
//   participants  = účastníci
//   enrollments   = přihlášky (spojují účastníka s kurzem a mají stav)
//
// Vztahy: kurz 1:N přihlášky, účastník 1:N přihlášky
//         → kurz M:N účastník (přes tabulku enrollments)
// =====================================================================

// Zkratky pro třídy, které níže používáme
use Illuminate\Database\Migrations\Migration;   // základ každé migrace
use Illuminate\Database\Schema\Blueprint;        // "plánek" tabulky ($table->...)
use Illuminate\Support\Facades\Schema;           // vytváření a mazání tabulek

// Migrace je anonymní třída (bez jména), Laravel ji jen spustí
return new class extends Migration
{
    // up() = co se stane při `php artisan migrate`
    public function up(): void
    {
        // ---------- Tabulka KURZŮ ----------
        Schema::create('courses', function (Blueprint $table) {
            $table->id();                            // id: automaticky rostoucí číslo, primární klíč
            $table->string('name');                  // název kurzu (text do 255 znaků)
            $table->string('code')->unique();        // kód kurzu; unique() = databáze nedovolí dva stejné kódy
            $table->unsignedInteger('capacity');     // kapacita: celé číslo bez znaménka (nemůže být záporné)
            $table->dateTime('starts_at');           // datum a čas začátku kurzu
            $table->timestamps();                    // created_at + updated_at (Laravel je vyplňuje sám)
        });

        // ---------- Tabulka ÚČASTNÍKŮ ----------
        Schema::create('participants', function (Blueprint $table) {
            $table->id();                            // id účastníka
            $table->string('name');                  // jméno a příjmení
            $table->string('email')->unique();       // e-mail; unique() = každý e-mail jen jednou
            $table->timestamps();                    // created_at + updated_at
        });

        // ---------- Tabulka PŘIHLÁŠEK ----------
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();                            // id přihlášky
            // course_id = odkaz na kurz. constrained() vytvoří cizí klíč na courses.id,
            // cascadeOnDelete() = když se smaže kurz, smažou se i jeho přihlášky
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            // participant_id = odkaz na účastníka, smazáním účastníka zmizí i jeho přihlášky
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->dateTime('enrolled_at');         // kdy se účastník přihlásil
            $table->string('status')->default('enrolled'); // stav: enrolled / completed / cancelled
            // Pojistka přímo v databázi: stejný účastník nesmí mít v jednom kurzu dvě přihlášky
            $table->unique(['course_id', 'participant_id']);
        });
    }

    // down() = opak up(), spustí se při `php artisan migrate:rollback`
    public function down(): void
    {
        // Mažeme v opačném pořadí: přihlášky odkazují na kurzy a účastníky
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('participants');
        Schema::dropIfExists('courses');
    }
};
