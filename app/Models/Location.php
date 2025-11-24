<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'description', 'novel_id'];

    public function novel()
    {
        return $this->belongsTo(Novel::class);
    }
}
