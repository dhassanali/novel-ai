<?php

namespace App\Models;

use App\Enums\ChapterStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chapter extends Model
{
    use HasFactory;

    protected $fillable = ['novel_id', 'title', 'content', 'order', 'word_count', 'status', 'pov_character_id'];

    protected function casts(): array
    {
        return [
            'status' => ChapterStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Chapter $chapter) {
            if ($chapter->isDirty('content')) {
                $chapter->word_count = $chapter->calculateWordCount();
            }
        });

        static::saved(function (Chapter $chapter) {
            $chapter->novel->updateTotalWordCount();
        });

        static::deleted(function (Chapter $chapter) {
            $chapter->novel->updateTotalWordCount();
        });
    }

    public function novel(): BelongsTo
    {
        return $this->belongsTo(Novel::class);
    }

    public function povCharacter(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'pov_character_id');
    }

    public function calculateWordCount(): int
    {
        if (empty($this->content)) {
            return 0;
        }

        return str_word_count(strip_tags($this->content));
    }
}
