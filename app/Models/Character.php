<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Character extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'role', 'novel_id'];

    public function novel(): BelongsTo
    {
        return $this->belongsTo(Novel::class);
    }
}
