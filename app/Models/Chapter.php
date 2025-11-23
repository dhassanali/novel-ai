<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chapter extends Model
{
    protected $fillable = ['novel_id', 'title', 'content', 'order'];

    public function novel()
    {
        return $this->belongsTo(Novel::class);
    }
}
