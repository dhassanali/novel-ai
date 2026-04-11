<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Character extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'role',
        'personality_traits',
        'backstory',
        'goals',
        'speech_patterns',
        'novel_id',
    ];

    public function novel(): BelongsTo
    {
        return $this->belongsTo(Novel::class);
    }

    public function relationships(): HasMany
    {
        return $this->hasMany(CharacterRelationship::class)->with('relatedCharacter');
    }
}
