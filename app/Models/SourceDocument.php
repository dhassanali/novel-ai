<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SourceDocument extends Model
{
    use HasFactory;

    protected $fillable = ['novel_id', 'filename', 'path', 'status', 'type'];

    public function novel(): BelongsTo
    {
        return $this->belongsTo(Novel::class);
    }
}
