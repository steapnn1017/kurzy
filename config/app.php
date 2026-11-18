<?php

// =====================================================================
// config/app.php = jediný konfigurační soubor aplikace.
// Laravel 13 má výchozí nastavení přímo ve frameworku
// (vendor/laravel/framework/config), takže tady přepisujeme jen to,
// co chceme jinak. Všechno ostatní se doplní samo.
// =====================================================================

return [
    // Časové pásmo: termíny kurzů a čas přihlášení (now()) jsou v českém čase.
    // Bez tohoto řádku by Laravel počítal v UTC a čas by ukazoval o hodinu méně.
    'timezone' => 'Europe/Prague',
];
