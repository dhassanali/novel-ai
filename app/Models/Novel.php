<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Novel extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'description', 'genre', 'user_id', 'cover_image', 'total_word_count', 'word_count_goal'];

    protected $appends = ['cover_image_url'];

    protected function coverImageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->cover_image
            ? asset('storage/'.$this->cover_image)
            : null
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class)->orderBy('order');
    }

    public function sourceDocuments(): HasMany
    {
        return $this->hasMany(SourceDocument::class);
    }

    public function characters(): HasMany
    {
        return $this->hasMany(Character::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function updateTotalWordCount(): void
    {
        $this->total_word_count = $this->chapters()->sum('word_count');
        $this->saveQuietly();
    }
}
