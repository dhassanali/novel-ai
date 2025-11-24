<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Novel extends Model
{
    protected $fillable = ['title', 'description', 'genre', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function chapters()
    {
        return $this->hasMany(Chapter::class)->orderBy('order');
    }

    public function sourceDocuments()
    {
        return $this->hasMany(SourceDocument::class);
    }

    public function characters()
    {
        return $this->hasMany(Character::class);
    }

    public function locations()
    {
        return $this->hasMany(Location::class);
    }
}
