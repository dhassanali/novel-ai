<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterRelationship extends Model
{
    protected $fillable = [
        'novel_id',
        'character_id',
        'related_character_id',
        'type',
        'description',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function relatedCharacter(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'related_character_id');
    }

    public function novel(): BelongsTo
    {
        return $this->belongsTo(Novel::class);
    }
}
