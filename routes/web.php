<?php

// =====================================================================
// routes/web.php = všechny adresy (URL) aplikace a kdo je obslouží.
// Výpis všech adres v terminálu: php artisan route:list
// =====================================================================

use App\Http\Controllers\CourseController;       // kurzy + přihlášky
use App\Http\Controllers\ParticipantController;  // účastníci
use Illuminate\Support\Facades\Route;            // třída pro zápis adres

// Úvodní adresa "/" jen přesměruje na seznam kurzů
Route::redirect('/', '/courses');

// Route::resource vytvoří najednou 7 adres pro CRUD kurzů
// (index, create, store, show, edit, update, destroy) a pojmenuje je
// courses.index, courses.show... Šablony pak píšou route('courses.show', $course).
Route::resource('courses', CourseController::class);

// Totéž pro účastníky, jen bez detailu (show), ten nepotřebujeme
Route::resource('participants', ParticipantController::class)->except('show');

// Přihlášení účastníka do kurzu (formulář na detailu kurzu)
Route::post('courses/{course}/enroll', [CourseController::class, 'enroll'])->name('courses.enroll');

// Změna stavu přihlášky. PATCH = částečná úprava (měníme jen sloupec status).
Route::patch('enrollments/{enrollment}', [CourseController::class, 'changeStatus'])->name('enrollments.status');
