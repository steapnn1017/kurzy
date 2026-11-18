{{-- =====================================================================
     resources/views/layout.blade.php = SPOLEČNÁ KOSTRA všech stránek.
     Obsahuje <head>, navigaci, vyskakovací hlášku (toast) a CELÉ CSS
     aplikace (nepoužíváme npm ani Vite, styly jsou přímo tady).
     Ostatní šablony začínají @extends('layout') a svůj obsah posílají
     přes @section('content'), který se vloží na místo @yield('content').
     Blade komentáře (jako tento) se do prohlížeče vůbec neposílají.
     ===================================================================== --}}
<!DOCTYPE html>
{{-- lang="cs" = stránka je česky (čtečky obrazovky, dělení slov) --}}
<html lang="cs">
<head>
    {{-- Kódování znaků UTF-8, aby fungovala čeština --}}
    <meta charset="utf-8">
    {{-- Na mobilu se stránka zobrazí v šířce displeje (žádné zmenšení) --}}
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Titulek záložky: každá stránka si část doplní přes @section('title') --}}
    <title>@yield('title') · Kurzy</title>
    {{-- Ikona záložky: malé SVG (tři sloupečky = obsazenost) zapsané přímo v adrese, žádný soubor navíc --}}
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%230b0c0e'/%3E%3Crect x='8' y='17' width='4' height='7' rx='1' fill='%23ece8df'/%3E%3Crect x='14' y='12' width='4' height='12' rx='1' fill='%23ece8df'/%3E%3Crect x='20' y='7' width='4' height='17' rx='1' fill='%23ece8df'/%3E%3C/svg%3E">

    <style>
        /* =============================================================
           1) PÍSMO: systémové písmo zařízení (na Macu a iPhonu SF Pro,
              na Windows Segoe UI). Nic se nestahuje, žádný soubor navíc
              a žádná data třetí straně. Nastavené je níže v body.
           ============================================================= */

        /* =============================================================
           2) BARVY A ROZMĚRY jako proměnné (mění se na jednom místě)
           ============================================================= */
        :root {
            --bg: #0b0c0e;          /* pozadí stránky: skoro černá */
            --panel: #121316;       /* plocha karet a panelů */
            --panel-2: #17181c;     /* plocha vstupních polí */
            --line: #26272b;        /* tenké linky a rámečky */
            --line-2: #34353a;      /* výraznější linka (najetí myší) */
            --text: #ece8df;        /* hlavní text: teplá "kostní" bílá */
            --muted: #8c8a84;       /* vedlejší text (popisky, data) */
            --green: #9fcaa7;       /* stav Přihlášeno, volná místa */
            --blue: #a9bdd1;        /* stav Dokončeno */
            --amber: #dcb872;       /* skoro plný kurz (od 75 %) */
            --red: #e39a8f;         /* plný kurz, zrušená přihláška, chyby */
            --r-card: 16px;         /* zaoblení karet a panelů */
            --r-input: 10px;        /* zaoblení vstupních polí (tlačítka a štítky jsou celé kulaté) */
            --ease: cubic-bezier(.22, 1, .36, 1); /* křivka pohybu: rychlý start, jemné dojetí */
        }

        /* =============================================================
           3) ZÁKLAD
           ============================================================= */

        /* Šířka prvku včetně rámečku a vnitřního okraje (snazší počítání rozměrů) */
        *, *::before, *::after { box-sizing: border-box; }

        html {
            color-scheme: dark;              /* tmavé ovládací prvky prohlížeče (kalendář, posuvník) */
            -webkit-text-size-adjust: 100%;  /* iPhone nezvětšuje písmo sám od sebe */
        }

        body {
            margin: 0;                                        /* bez výchozího okraje prohlížeče */
            min-height: 100dvh;                               /* aspoň na celou výšku okna (dvh funguje i na mobilu) */
            background:                                       /* pozadí: */
                radial-gradient(900px 420px at 50% -160px, rgba(236, 232, 223, .07), transparent 70%) no-repeat, /* jemné světlo nahoře */
                var(--bg);                                    /* pod ním tmavá barva */
            color: var(--text);                               /* barva textu */
            font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif; /* systémové písmo (Mac, iPhone, Windows), poslední = jakékoli bezpatkové */
            font-size: 15px;                                  /* základní velikost textu */
            line-height: 1.6;                                 /* řádkování (čitelnost) */
            -webkit-font-smoothing: antialiased;              /* jemnější vykreslení písma na Macu */
            -webkit-tap-highlight-color: transparent;         /* na mobilu bez modrého záblesku při ťuknutí */
        }

        /* Odkazy bez podtržení, barvu dědí z okolí */
        a { color: inherit; text-decoration: none; }

        /* Nadpisy a odstavce bez výchozích mezer (mezery řeší rozložení) */
        h1, h2, p { margin: 0; }

        /* Viditelný obrys při ovládání klávesnicí (Tab), přístupnost */
        :focus-visible { outline: 2px solid var(--text); outline-offset: 2px; }

        /* Text jen pro čtečky obrazovky (vizuálně skrytý) */
        .sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip-path: inset(50%); white-space: nowrap; }

        /* Pomocné třídy */
        .muted { color: var(--muted); }          /* šedý text */
        .nowrap { white-space: nowrap; }         /* nezalamovat řádek (datum) */

        /* =============================================================
           4) NAVIGACE: plovoucí "pilulka" s rozmazaným pozadím
           ============================================================= */
        .nav-wrap {
            position: sticky;   /* při posouvání zůstane nahoře */
            top: 0;             /* přilepená k hornímu okraji */
            z-index: 20;        /* nad obsahem stránky */
            padding: 12px 16px; /* odsazení od okrajů okna */
        }

        .nav {
            max-width: 1120px;                                  /* stejná šířka jako obsah */
            margin: 0 auto;                                     /* na střed */
            display: grid;                                      /* tři sloupce: logo | odkazy | tlačítko */
            grid-template-columns: 1fr auto 1fr;                /* krajní sloupce stejně široké → odkazy přesně uprostřed */
            align-items: center;                                /* svisle na střed */
            gap: 12px;                                          /* mezera mezi sloupci */
            height: 58px;                                       /* výška lišty */
            padding: 0 8px 0 20px;                              /* vnitřní okraj (vpravo méně kvůli tlačítku) */
            border: 1px solid var(--line);                      /* tenký rámeček */
            border-radius: 999px;                               /* úplně kulaté konce */
            background: rgba(18, 19, 22, .72);                  /* poloprůhledné pozadí */
            backdrop-filter: blur(16px) saturate(140%);         /* rozmazání obsahu pod lištou (efekt matného skla) */
            -webkit-backdrop-filter: blur(16px) saturate(140%); /* totéž pro Safari */
        }

        /* Logo vlevo */
        .brand { justify-self: start; display: inline-flex; align-items: center; gap: 10px; font-weight: 600; letter-spacing: -.01em; }
        /* Značka: tři sloupečky rostoucí výšky (jako obsazenost) */
        .brand-mark { display: inline-flex; align-items: flex-end; gap: 2px; height: 16px; }
        .brand-mark i { width: 4px; border-radius: 1px; background: var(--text); }   /* jeden sloupeček */
        .brand-mark i:nth-child(1) { height: 7px; }                                   /* nízký */
        .brand-mark i:nth-child(2) { height: 11px; }                                  /* střední */
        .brand-mark i:nth-child(3) { height: 16px; }                                  /* vysoký */

        /* Odkazy uprostřed */
        .nav-links { display: flex; gap: 2px; }
        .nav-links a {
            position: relative;           /* kvůli podtržení ::after */
            padding: 8px 14px;            /* velká klikací plocha */
            color: var(--muted);          /* neaktivní odkaz je šedý */
            font-weight: 500;             /* středně tučné */
            transition: color .15s;       /* plynulá změna barvy */
        }
        .nav-links a:hover, .nav-links a.active { color: var(--text); }   /* najetí myší / aktivní stránka = světlý */
        .nav-links a.active::after {      /* tenké podtržení aktivní stránky */
            content: '';                  /* prázdný prvek, jen čára */
            position: absolute;           /* umístění vůči odkazu */
            left: 14px; right: 14px;      /* stejně široké jako text */
            bottom: 2px;                  /* těsně pod textem */
            height: 1.5px;                /* tloušťka čáry */
            border-radius: 2px;           /* zaoblené konce */
            background: currentColor;     /* barva podle textu */
        }

        /* Tlačítko vpravo */
        .nav .btn { justify-self: end; }

        /* =============================================================
           5) STRÁNKA A NADPISY
           ============================================================= */
        .page { max-width: 1120px; margin: 0 auto; padding: 40px 16px 96px; } /* obsah na střed s okraji */

        /* Hlavička stránky: nadpis vlevo, tlačítka vpravo (na mobilu pod sebou) */
        .page-head { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 20px; margin-bottom: 28px; }

        h1 {
            font-size: clamp(2.2rem, 1.4rem + 3vw, 3.4rem); /* velikost roste se šířkou okna, ale jen v mezích */
            font-weight: 600;                               /* tučný */
            line-height: 1.04;                              /* těsné řádkování velkého nadpisu */
            letter-spacing: -.035em;                        /* písmena blíž k sobě (moderní vzhled) */
            text-wrap: balance;                             /* víceřádkový nadpis se zalomí do vyrovnaných řádků */
        }

        .lead { margin-top: 10px; color: var(--muted); max-width: 60ch; } /* podnadpis, max. 60 znaků na řádek */
        .code { font-size: 12px; font-weight: 500; letter-spacing: .06em; color: var(--muted); font-variant-numeric: tabular-nums; } /* kód kurzu */
        .back { display: inline-flex; margin-bottom: 18px; color: var(--muted); font-size: 14px; transition: color .15s; } /* odkaz zpět */
        .back:hover { color: var(--text); }
        .head-actions { display: flex; gap: 8px; }                         /* tlačítka v hlavičce vedle sebe */
        .count { margin: 0 0 14px 2px; font-size: 13px; color: var(--muted); } /* "7 kurzů" nad mřížkou */

        /* =============================================================
           6) TLAČÍTKA (všechna mají kulatý tvar pilulky)
           ============================================================= */
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px; /* obsah na střed, ikona + text */
            height: 42px;                     /* stejná výška jako vstupní pole */
            padding: 0 18px;                  /* vodorovný vnitřní okraj */
            border: 1px solid transparent;    /* rámeček (průhledný, ať mají všechna tlačítka stejnou velikost) */
            border-radius: 999px;             /* tvar pilulky */
            font: inherit;                    /* písmo jako okolí (button ho jinak nedědí) */
            font-size: 14px;                  /* velikost textu */
            font-weight: 500;                 /* středně tučné */
            white-space: nowrap;              /* text se nezalomí */
            cursor: pointer;                  /* ručička při najetí */
            user-select: none;                /* text nejde omylem označit */
            transition: background-color .15s, border-color .15s, color .15s, transform .16s var(--ease); /* plynulé změny */
        }
        .btn:active { transform: scale(.97); }                                    /* při stisku se lehce zmáčkne (odezva) */
        .btn-primary { background: var(--text); color: var(--bg); }              /* hlavní: světlé s tmavým textem */
        .btn-primary:hover { background: #f7f4ed; }                               /* o kousek světlejší */
        .btn-ghost { border-color: var(--line); background: transparent; color: var(--text); } /* vedlejší: jen rámeček */
        .btn-ghost:hover { border-color: var(--line-2); background: var(--panel-2); }
        .btn-danger { border-color: rgba(227, 154, 143, .25); background: transparent; color: var(--red); } /* smazání: červený text */
        .btn-danger:hover { background: rgba(227, 154, 143, .08); }
        .btn-sm { height: 32px; padding: 0 12px; font-size: 13px; }              /* menší varianta do tabulky */
        .btn-block { width: 100%; }                                               /* přes celou šířku */

        /* =============================================================
           7) FORMULÁŘE
           ============================================================= */
        .form { display: flex; flex-direction: column; gap: 20px; max-width: 640px; padding: 28px; } /* formulář pod sebou s mezerami */
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }  /* dvě pole vedle sebe */
        .form-actions { display: flex; gap: 10px; padding-top: 4px; }            /* tlačítka Uložit / Zrušit */
        .stack { display: flex; flex-direction: column; gap: 12px; }             /* prvky pod sebou */

        .field { display: flex; flex-direction: column; gap: 6px; min-width: 0; } /* popisek nad polem, chyba pod ním */
        .field label { font-size: 13px; font-weight: 500; color: var(--muted); } /* popisek pole */

        .input {
            width: 100%;                                     /* přes celou šířku */
            height: 42px;                                    /* výška pole */
            padding: 0 14px;                                 /* text není nalepený na okraj */
            border: 1px solid var(--line);                   /* rámeček */
            border-radius: var(--r-input);                   /* zaoblení */
            background: var(--panel-2);                      /* tmavá plocha */
            color: var(--text);                              /* barva textu */
            font: inherit;                                   /* písmo jako okolí */
            font-size: 16px;                                 /* 16px = iPhone při kliknutí stránku nepřiblíží */
            appearance: none;                                /* vypne výchozí vzhled prohlížeče */
            -webkit-appearance: none;                        /* totéž pro Safari */
            transition: border-color .15s, box-shadow .15s;  /* plynulé zvýraznění */
        }
        .input::placeholder { color: var(--muted); }         /* ukázkový text v poli */
        .input:hover { border-color: var(--line-2); }         /* najetí myší */
        .input:focus { outline: none; border-color: #5a5853; box-shadow: 0 0 0 3px rgba(236, 232, 223, .08); } /* aktivní pole = světlejší rámeček + jemná záře */
        .input.invalid { border-color: rgba(227, 154, 143, .6); }                 /* pole s chybou má červený rámeček */
        .input::-webkit-date-and-time-value { text-align: left; }                 /* Safari: datum zarovnané vlevo */

        /* Rozbalovací seznam: vlastní šipka (výchozí jsme vypnuli přes appearance: none) */
        select.input {
            padding-right: 40px;                     /* místo pro šipku */
            cursor: pointer;                         /* ručička */
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath d='M3 4.5l3 3 3-3' fill='none' stroke='%238c8a84' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E"); /* šipka dolů jako malé SVG */
            background-repeat: no-repeat;            /* šipka jen jednou */
            background-position: right 14px center;  /* vpravo uprostřed */
        }

        .hint { font-size: 12.5px; color: var(--muted); }   /* nápověda pod polem */
        .error { font-size: 13px; color: var(--red); }      /* chybová hláška pod polem */

        /* Přepínač "Jen s volnými místy": skutečný checkbox je schovaný, kreslíme vlastní */
        .switch { position: relative; display: inline-flex; align-items: center; gap: 10px; height: 42px; cursor: pointer; font-weight: 500; user-select: none; }
        .switch input { position: absolute; opacity: 0; width: 1px; height: 1px; } /* neviditelný, ale klávesnicí i čtečkou dosažitelný */
        .switch-track {                    /* dráha přepínače */
            position: relative;            /* kvůli kolečku ::after */
            flex: none;                    /* nezmenší se */
            width: 38px; height: 22px;     /* rozměr dráhy */
            border-radius: 999px;          /* kulatá */
            background: var(--line);       /* vypnuto = šedá */
            transition: background-color .2s; /* plynulá změna barvy */
        }
        .switch-track::after {             /* kolečko */
            content: '';                   /* prázdný prvek */
            position: absolute;            /* uvnitř dráhy */
            top: 3px; left: 3px;           /* odsazení od okraje dráhy */
            width: 16px; height: 16px;     /* velikost kolečka */
            border-radius: 50%;            /* kruh */
            background: var(--muted);      /* šedé kolečko */
            transition: transform .25s var(--ease), background-color .2s; /* plynulý posun */
        }
        .switch input:checked + .switch-track { background: var(--text); }                                    /* zapnuto = světlá dráha */
        .switch input:checked + .switch-track::after { transform: translateX(16px); background: var(--bg); } /* kolečko doprava */
        .switch input:focus-visible + .switch-track { outline: 2px solid var(--text); outline-offset: 2px; } /* obrys při ovládání klávesnicí */

        /* =============================================================
           8) FILTR KURZŮ
           ============================================================= */
        .filters {
            display: flex; flex-wrap: wrap;   /* prvky vedle sebe, na úzké obrazovce se zalomí */
            align-items: flex-end;            /* zarovnané dolů (pole mají nad sebou popisek) */
            gap: 12px 16px;                   /* mezery mezi řádky / sloupci */
            padding: 16px;                    /* vnitřní okraj panelu */
            margin-bottom: 16px;              /* mezera pod filtrem */
            border: 1px solid var(--line);    /* rámeček */
            border-radius: var(--r-card);     /* zaoblení */
            background: var(--panel);         /* plocha panelu */
        }
        .filters .field { flex: 1 1 170px; max-width: 220px; }     /* datumová pole */
        .filters-actions { display: flex; gap: 8px; margin-left: auto; } /* tlačítka vpravo */

        /* =============================================================
           9) KARTY KURZŮ v mřížce
           ============================================================= */
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 320px), 1fr)); gap: 14px; } /* kolik se vejde sloupců po min. 320px */

        .card {
            display: flex; flex-direction: column; gap: 10px;    /* obsah karty pod sebou */
            padding: 20px;                                       /* vnitřní okraj */
            border: 1px solid var(--line);                       /* rámeček */
            border-radius: var(--r-card);                        /* zaoblení */
            background: var(--panel);                            /* plocha karty */
            transition: border-color .2s, background-color .2s, transform .35s var(--ease); /* plynulé zvednutí */
            animation: rise .6s var(--ease) backwards;           /* při načtení vyjede zespodu */
            animation-delay: calc(min(var(--i, 0), 12) * 45ms);  /* postupně: každá další o 45 ms později (--i nastaví šablona) */
        }
        /* Zvednutí jen na zařízeních s myší (na mobilu by efekt "zůstal viset") */
        @media (hover: hover) {
            .card:hover { border-color: var(--line-2); background: #15161a; transform: translateY(-2px); }
        }
        .card-top { display: flex; align-items: center; justify-content: space-between; gap: 10px; } /* kód vlevo, štítek vpravo */
        .card-title { font-size: 19px; font-weight: 600; line-height: 1.3; letter-spacing: -.015em; } /* název kurzu */
        .card-date { color: var(--muted); font-size: 14px; font-variant-numeric: tabular-nums; }     /* termín */
        .card .meter { margin-top: 10px; }                                                          /* ukazatel trochu odsadit */

        /* =============================================================
           10) ŠTÍTKY (chips): barva podle stavu
           ============================================================= */
        .chip {
            display: inline-flex; align-items: center; /* text svisle na střed */
            height: 24px; padding: 0 10px;              /* malá pilulka */
            border: 1px solid var(--line);              /* rámeček */
            border-radius: 999px;                       /* kulatá */
            color: var(--muted);                        /* výchozí šedá (např. "Proběhl") */
            font-size: 12px; font-weight: 500;          /* malé písmo */
            white-space: nowrap;                        /* nezalamovat */
            transition: border-color .15s;              /* plynulý rámeček (odkazy) */
        }
        a.chip:hover { border-color: var(--line-2); }   /* štítek-odkaz při najetí */
        .chips { display: flex; flex-wrap: wrap; gap: 6px; } /* skupina štítků */
        /* zelená: přihlášeno, volná místa */
        .chip-enrolled, .chip-free { color: var(--green); border-color: rgba(159, 202, 167, .22); background: rgba(159, 202, 167, .08); }
        /* modrá: dokončeno */
        .chip-completed { color: var(--blue); border-color: rgba(169, 189, 209, .22); background: rgba(169, 189, 209, .08); }
        /* červená: zrušeno, plno */
        .chip-cancelled, .chip-full { color: var(--red); border-color: rgba(227, 154, 143, .22); background: rgba(227, 154, 143, .08); }
        .chip-cancelled { text-decoration: line-through; } /* zrušená přihláška je navíc přeškrtnutá (nejen barva) */

        /* =============================================================
           11) UKAZATEL OBSAZENOSTI
           ============================================================= */
        .meter { height: 6px; border-radius: 999px; background: var(--line); overflow: hidden; } /* šedá dráha */
        .meter span {
            display: block; height: 100%;            /* výplň přes celou výšku dráhy */
            background: var(--text);                 /* výchozí barva (ok) */
            transform-origin: left;                  /* roste zleva */
            transform: scaleX(var(--p));             /* délka = obsazenost 0 až 1 (proměnnou --p nastaví šablona) */
            animation: fill .9s var(--ease) backwards; /* při načtení se "naplní" */
        }
        .meter[data-level="high"] span { background: var(--amber); } /* od 75 % žlutá */
        .meter[data-level="full"] span { background: var(--red); }   /* plno červená */
        .meter-label { display: flex; justify-content: space-between; gap: 8px; font-size: 13px; color: var(--muted); font-variant-numeric: tabular-nums; } /* popisek pod ukazatelem */
        .meter-label strong { color: var(--text); font-weight: 600; } /* číslo zvýrazněné */

        /* =============================================================
           12) PANELY A DETAIL KURZU
           ============================================================= */
        .panel { border: 1px solid var(--line); border-radius: var(--r-card); background: var(--panel); } /* obecný panel */
        .panel-title { font-size: 15px; font-weight: 600; letter-spacing: -.01em; }                      /* nadpis panelu */
        .panel-head { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; padding: 18px 20px 6px; } /* hlavička panelu se seznamem */

        /* Detail: vlevo seznam přihlášek, vpravo obsazenost + formulář */
        .detail {
            display: grid;                                  /* mřížka */
            grid-template-columns: minmax(0, 1fr) 340px;    /* levý sloupec pružný, pravý 340px */
            grid-template-areas: "list side";               /* pojmenované oblasti (pořadí v HTML může být jiné) */
            gap: 16px;                                      /* mezera mezi sloupci */
            align-items: start;                             /* sloupce nejsou natažené na stejnou výšku */
        }
        .list { grid-area: list; }                                                                    /* seznam přihlášek vlevo */
        .side { grid-area: side; display: flex; flex-direction: column; gap: 16px; position: sticky; top: 92px; } /* pravý sloupec zůstává při posouvání vidět */
        .side .panel { display: flex; flex-direction: column; gap: 14px; padding: 20px; }             /* panely v pravém sloupci */

        .big { font-size: 44px; font-weight: 600; line-height: 1; letter-spacing: -.04em; font-variant-numeric: tabular-nums; } /* velké číslo obsazenosti */
        .big span { font-size: 22px; color: var(--muted); letter-spacing: -.02em; }   /* "/ 12" menší a šedé */

        .stats { display: grid; grid-template-columns: repeat(3, 1fr); margin: 0; padding-top: 14px; border-top: 1px solid var(--line); } /* tři čísla vedle sebe */
        .stats dt { font-size: 12px; color: var(--muted); }                                               /* popisek čísla */
        .stats dd { margin: 2px 0 0; font-size: 17px; font-weight: 600; font-variant-numeric: tabular-nums; } /* číslo */

        .note { font-size: 14px; color: var(--muted); }                                   /* poznámka v panelu */
        .note a { color: var(--text); text-decoration: underline; text-underline-offset: 3px; } /* odkaz v poznámce */
        .note-red { color: var(--red); }                                                  /* varování (kurz je plný) */

        /* =============================================================
           13) TABULKA A PŘEPÍNAČ STAVU
           ============================================================= */
        .table { width: 100%; border-collapse: collapse; }              /* tabulka přes celou šířku, bez dvojitých čar */
        .table th { padding: 14px 16px 10px; text-align: left; font-size: 12px; font-weight: 500; color: var(--muted); } /* záhlaví */
        .table td { padding: 14px 16px; border-top: 1px solid var(--line); vertical-align: middle; }                     /* buňka s linkou nad řádkem */
        .table tbody tr { transition: background-color .15s; }          /* plynulé podbarvení */
        .table tbody tr:hover { background: rgba(255, 255, 255, .015); } /* jemné podbarvení řádku pod myší */
        .name { display: block; font-weight: 500; }                    /* jméno účastníka */
        .sub { display: block; font-size: 13px; color: var(--muted); } /* e-mail pod jménem */
        .is-cancelled .name { color: var(--muted); }                   /* zrušená přihláška = šedé jméno */
        .actions { display: flex; justify-content: flex-end; gap: 6px; } /* tlačítka Upravit / Smazat vpravo */
        .actions form { margin: 0; }                                   /* formulář nepřidává mezeru */

        /* Segmentový přepínač stavu: tři tlačítka v jedné "pilulce" */
        .seg { display: inline-flex; gap: 2px; padding: 3px; border: 1px solid var(--line); border-radius: 999px; background: var(--bg); }
        .seg button {
            height: 28px; padding: 0 10px;         /* velikost segmentu */
            border: 0; border-radius: 999px;       /* bez rámečku, kulatý */
            background: transparent;               /* neaktivní je průhledný */
            color: var(--muted);                   /* šedý text */
            font: inherit; font-size: 13px; font-weight: 500; /* písmo */
            cursor: pointer;                       /* ručička */
            transition: background-color .2s, color .2s, transform .16s var(--ease); /* plynulé změny */
        }
        .seg button:hover { color: var(--text); }                    /* najetí myší */
        .seg button:active { transform: scale(.96); }               /* odezva při stisku */
        .seg .on.seg-enrolled { background: rgba(159, 202, 167, .14); color: var(--green); } /* aktivní Přihlášeno */
        .seg .on.seg-completed { background: rgba(169, 189, 209, .14); color: var(--blue); } /* aktivní Dokončeno */
        .seg .on.seg-cancelled { background: rgba(227, 154, 143, .14); color: var(--red); }  /* aktivní Zrušeno */

        /* =============================================================
           14) PRÁZDNÉ STAVY
           ============================================================= */
        .empty { display: flex; flex-direction: column; align-items: center; gap: 14px; padding: 56px 24px; text-align: center; border: 1px solid var(--line); border-radius: var(--r-card); background: var(--panel); } /* velký prázdný stav */
        .empty p { color: var(--muted); }                       /* text prázdného stavu */
        .empty-inline { padding: 10px 20px 26px; color: var(--muted); } /* prázdný seznam uvnitř panelu */
        .pad { padding: 0 20px 10px; }                          /* odsazení chyby v panelu */

        /* =============================================================
           15) VYSKAKOVACÍ HLÁŠKA (toast) po uložení
               Bez JavaScriptu: objeví se animací a po 3,5 s sama zmizí.
           ============================================================= */
        .toast {
            position: fixed;                        /* pevně v okně, i když se posouvá */
            left: 50%; bottom: 24px;                /* dole uprostřed */
            translate: -50% 0;                      /* přesně na střed (posun o polovinu vlastní šířky) */
            z-index: 30;                            /* nad vším ostatním */
            display: flex; align-items: center; gap: 10px; /* ikona + text */
            padding: 12px 20px;                     /* vnitřní okraj */
            border-radius: 999px;                   /* pilulka */
            background: var(--text);                /* světlá */
            color: var(--bg);                       /* tmavý text */
            font-size: 14px; font-weight: 500;      /* písmo */
            box-shadow: 0 12px 32px rgba(0, 0, 0, .45); /* stín pod hláškou */
            pointer-events: none;                   /* nepřekáží klikání */
            animation: toast-in .45s var(--ease) backwards, toast-out .3s var(--ease) 3.5s forwards; /* příjezd, za 3,5 s odjezd */
        }

        /* =============================================================
           16) ANIMACE (pohyb jen přes transform a opacity = plynulé)
           ============================================================= */
        @keyframes rise { from { opacity: 0; transform: translateY(10px); } }            /* karta vyjede o 10px zespodu */
        @keyframes fill { from { transform: scaleX(0); } }                               /* ukazatel roste z nuly */
        @keyframes toast-in { from { opacity: 0; transform: translateY(16px) scale(.97); } } /* hláška přijede zespodu */
        @keyframes toast-out { to { opacity: 0; transform: translateY(8px); } }          /* hláška odjede dolů */
        @keyframes toast-fade { to { opacity: 0; } }                                     /* jen zmizení (bez pohybu) */

        /* Uživatel si v systému vypnul animace → žádný pohyb, hláška jen zmizí */
        @media (prefers-reduced-motion: reduce) {
            .card, .meter span { animation: none; }                       /* karty a ukazatel hned na místě */
            .card:hover { transform: none; }                              /* karta se nezvedá */
            .toast { animation: toast-fade .3s linear 3.5s forwards; }    /* hláška jen vybledne */
        }

        /* =============================================================
           17) MOBIL a úzká okna
           ============================================================= */
        @media (max-width: 860px) {
            .detail { grid-template-columns: 1fr; grid-template-areas: "side" "list"; } /* detail: obsazenost a formulář nahoře, seznam pod tím */
            .side { position: static; }                                                 /* pravý sloupec už nemusí být přilepený */
        }

        @media (max-width: 640px) {
            .page { padding-top: 28px; }                        /* menší horní mezera */
            .nav { padding-left: 16px; }                        /* menší okraj lišty */
            .brand-text, .cta-text { display: none; }           /* schovat text loga a tlačítka, zůstanou ikony */
            .nav .btn { width: 42px; padding: 0; }              /* tlačítko jen s ikonou + */
            .form { padding: 20px; }                            /* menší okraje formuláře */
            .form-row { grid-template-columns: 1fr; }           /* pole pod sebou */
            .filters .field { max-width: none; }                /* datumová pole přes celou šířku */
            .filters-actions { width: 100%; margin-left: 0; }   /* tlačítka filtru přes celou šířku */
            .filters-actions .btn { flex: 1; }                  /* rovným dílem */
            .table thead { display: none; }                     /* tabulka bez záhlaví... */
            .table tr { display: grid; gap: 10px; padding: 14px 16px; border-top: 1px solid var(--line); } /* ...řádek jako karta */
            .table td { padding: 0; border: 0; }                /* buňky pod sebou bez linek */
            .actions { justify-content: flex-start; }           /* tlačítka vlevo */
            .stats dd { font-size: 16px; }                      /* čísla trochu menší */
        }
    </style>
</head>
<body>
    {{-- ===== NAVIGACE ===== --}}
    <header class="nav-wrap">
        <nav class="nav" aria-label="Hlavní navigace">
            {{-- Logo: odkaz na seznam kurzů. route('courses.index') = adresa podle jména routy --}}
            <a class="brand" href="{{ route('courses.index') }}">
                {{-- Tři sloupečky (aria-hidden = čtečka je přeskočí, je to jen ozdoba) --}}
                <span class="brand-mark" aria-hidden="true"><i></i><i></i><i></i></span>
                {{-- Text loga (na mobilu se schová) --}}
                <span class="brand-text">Kurzy a přihlášky</span>
            </a>

            {{-- Odkazy uprostřed. @class přidá třídu "active", když platí podmínka.
                 routeIs('courses.*') = aktuální stránka je jakákoli stránka kurzů. --}}
            <div class="nav-links">
                <a href="{{ route('courses.index') }}" @class(['active' => request()->routeIs('courses.*')])>Kurzy</a>
                <a href="{{ route('participants.index') }}" @class(['active' => request()->routeIs('participants.*')])>Účastníci</a>
            </div>

            {{-- Tlačítko vpravo: na stránkách účastníků přidává účastníka, jinak kurz --}}
            @if (request()->routeIs('participants.*'))
                <a class="btn btn-primary" href="{{ route('participants.create') }}" aria-label="Nový účastník">
                    {{-- Ikona plus (dvě čáry) --}}
                    <svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 3v10M3 8h10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                    <span class="cta-text">Nový účastník</span>
                </a>
            @else
                <a class="btn btn-primary" href="{{ route('courses.create') }}" aria-label="Nový kurz">
                    <svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 3v10M3 8h10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                    <span class="cta-text">Nový kurz</span>
                </a>
            @endif
        </nav>
    </header>

    {{-- ===== OBSAH STRÁNKY: sem se vloží @section('content') z konkrétní šablony ===== --}}
    <main class="page">
        @yield('content')
    </main>

    {{-- ===== VYSKAKOVACÍ HLÁŠKA =====
         session('ok') = jednorázová zpráva z controlleru (->with('ok', '...')).
         Zobrazí se jen jednou, po dalším načtení stránky už v session není. --}}
    @if (session('ok'))
        {{-- role="status" = čtečka obrazovky zprávu přečte nahlas --}}
        <div class="toast" role="status">
            {{-- Ikona fajfky --}}
            <svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true"><path d="M3.5 8.5l3 3 6-7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            {{-- {{ }} vypíše text bezpečně (HTML znaky se převedou, nejde vložit kód) --}}
            {{ session('ok') }}
        </div>
    @endif
</body>
</html>
