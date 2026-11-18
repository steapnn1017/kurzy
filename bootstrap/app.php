<?php

// =====================================================================
// bootstrap/app.php = tady se "poskládá" celá aplikace.
// Načítá ho public/index.php (web), artisan (terminál) i testy.
// Říkáme tu hlavně, ve kterém souboru jsou adresy (routy).
// =====================================================================

// Zkratka pro hlavní třídu Laravelu
use Illuminate\Foundation\Application;

// configure() vytvoří aplikaci. basePath = kořen projektu:
// __DIR__ je složka bootstrap/, dirname() z ní udělá složku o úroveň výš.
return Application::configure(basePath: dirname(__DIR__))
    // Webové adresy (kurzy, účastníci, přihlášky) jsou v routes/web.php
    ->withRouting(web: __DIR__.'/../routes/web.php')
    // Middleware = kód, který proběhne před/po každém požadavku
    // (session, ochrana formulářů CSRF tokenem...). Necháváme výchozí sadu,
    // ale samotné volání je nutné, jinak by se nezaregistrovala.
    ->withMiddleware(function ($middleware) {})
    // Zpracování chyb (chybové stránky, přesměrování zpět při chybě
    // ve formuláři). Taky výchozí, volání je ale opět nutné.
    ->withExceptions(function ($exceptions) {})
    // create() vrátí hotovou aplikaci
    ->create();
