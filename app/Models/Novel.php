<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Novel extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'description', 'genre', 'user_id', 'cover_image', 'total_word_count'];

    protected $appends = ['cover_image_url'];

    public function getCoverImageUrlAttribute(): ?string
    {
        if ($this->cover_image) {
            return asset('storage/' . $this->cover_image);
        }
        return null;
    }

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

    public function updateTotalWordCount(): void
    {
        $this->total_word_count = $this->chapters()->sum('word_count');
        $this->saveQuietly(); // Save without triggering events
    }
}
