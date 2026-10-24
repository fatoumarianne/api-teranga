<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Message informant un utilisateur d'un evenement le concernant.
 */
class Notification extends Model
{
    use HasFactory;

    protected $table = 'notification';

    protected $fillable = [
        'id_utilisateur',
        'message',
        'lu',
    ];

    protected function casts(): array
    {
        return [
            'lu' => 'boolean',
            'date_envoi' => 'datetime',
        ];
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur');
    }

    /** Raccourci utilise par les controleurs pour notifier quelqu'un. */
    public static function envoyer(int $idUtilisateur, string $message): self
    {
        return self::create([
            'id_utilisateur' => $idUtilisateur,
            'message' => $message,
            'lu' => false,
        ]);
    }
}
