<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SourceDocument extends Model
{
    protected $fillable = ['novel_id', 'filename', 'path', 'status', 'type'];

    public function novel()
    {
        return $this->belongsTo(Novel::class);
    }
}
