<?php

// =====================================================================
// public/index.php = vstupní bod pro KAŽDÝ požadavek z prohlížeče.
// Složka public/ je jediná, kterou web server zveřejní. Všechno
// ostatní (kód, .env, databáze) zůstává mimo dosah prohlížeče.
// `php artisan serve` posílá všechny adresy právě sem.
// =====================================================================

// Zkratky pro třídy používané níže
use Illuminate\Foundation\Application;  // hlavní třída Laravelu
use Illuminate\Http\Request;            // objekt s daty požadavku (adresa, formulář, cookies)

// Čas začátku zpracování požadavku
define('LARAVEL_START', microtime(true));

// Composer autoloader (o složku výš, protože jsme v public/)
require __DIR__.'/../vendor/autoload.php';

// Načteme poskládanou aplikaci z bootstrap/app.php
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

// Request::capture() vezme požadavek z prohlížeče, aplikace ho zpracuje
// (najde routu, zavolá controller, vykreslí šablonu) a pošle odpověď zpět
$app->handleRequest(Request::capture());
