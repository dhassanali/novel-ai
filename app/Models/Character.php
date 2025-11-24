<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Character extends Model
{
    protected $fillable = ['name', 'description', 'role', 'novel_id'];

    public function novel()
    {
        return $this->belongsTo(Novel::class);
    }
}
