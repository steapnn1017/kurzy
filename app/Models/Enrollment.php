<?php

// =====================================================================
// app/Models/Enrollment.php = model PŘIHLÁŠKY (tabulka enrollments).
// Přihláška propojuje účastníka s kurzem a má vlastní stav.
// Sloupce: id, course_id, participant_id, enrolled_at, status
//
// Stavy (domácí práce):
//   enrolled  = přihlášen, místo je obsazené
//   completed = kurz dokončil, místo zůstává obsazené (kurzem prošel)
//   cancelled = přihláška zrušena, místo se uvolní pro někoho jiného
// =====================================================================

namespace App\Models; // třída leží ve složce app/Models

use Illuminate\Database\Eloquent\Model;                // základní třída všech modelů
use Illuminate\Database\Eloquent\Relations\BelongsTo;  // typ vztahu "patří k" (N:1)

class Enrollment extends Model
{
    // Všechny povolené stavy: klíč = hodnota v databázi, hodnota = český popisek do UI.
    // Pořadí určuje pořadí tlačítek v přepínači stavu na detailu kurzu.
    public const STATUSES = [
        'enrolled' => 'Přihlášeno',
        'completed' => 'Dokončeno',
        'cancelled' => 'Zrušeno',
    ];

    // Stavy, které ZABÍRAJÍ místo v kurzu. Podle nich Course::activeCount()
    // počítá obsazenost. Když chcete, aby místo zabíral jen "enrolled",
    // stačí odsud smazat 'completed'.
    public const ACTIVE = ['enrolled', 'completed'];

    // Tabulka nemá sloupce created_at/updated_at (čas vzniku je enrolled_at),
    // takže Laravelu říkáme, ať je nevyplňuje
    public $timestamps = false;

    // Sloupce, které smíme hromadně vyplnit
    protected $fillable = ['course_id', 'participant_id', 'enrolled_at', 'status'];

    // enrolled_at převádíme na objekt Carbon (kvůli ->format() v šablonách)
    protected function casts(): array
    {
        return ['enrolled_at' => 'datetime'];
    }

    // Vztah: přihláška PATŘÍ k jednomu kurzu (podle sloupce course_id)
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    // Vztah: přihláška PATŘÍ k jednomu účastníkovi (podle sloupce participant_id)
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    // Zabírá tahle přihláška místo? (true pro enrolled a completed)
    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE);
    }

    // Český popisek stavu, např. "Zrušeno" (pro štítky v seznamu účastníků)
    public function label(): string
    {
        return self::STATUSES[$this->status];
    }
}
