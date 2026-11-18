<?php

// =====================================================================
// app/Models/Participant.php = model ÚČASTNÍKA (tabulka participants).
// Sloupce: id, name, email, created_at, updated_at
// =====================================================================

namespace App\Models; // třída leží ve složce app/Models

use Illuminate\Database\Eloquent\Model;              // základní třída všech modelů
use Illuminate\Database\Eloquent\Relations\HasMany;  // typ vztahu "má mnoho" (1:N)

class Participant extends Model
{
    // Sloupce, které smíme hromadně vyplnit z formuláře
    protected $fillable = ['name', 'email'];

    // Vztah: jeden účastník má MNOHO přihlášek (do různých kurzů).
    // V seznamu účastníků podle nich vypisujeme kódy kurzů.
    public function enrollments(): HasMany
    {
        // V tabulce enrollments se hledá sloupec participant_id
        return $this->hasMany(Enrollment::class);
    }
}
