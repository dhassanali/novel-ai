<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chapter extends Model
{
    use HasFactory;
    protected $fillable = ['novel_id', 'title', 'content', 'order', 'word_count'];

    protected static function booted()
    {
        static::saving(function ($chapter) {
            // Auto-calculate word count when content changes
            if ($chapter->isDirty('content')) {
                $chapter->word_count = $chapter->calculateWordCount();
            }
        });

        static::saved(function ($chapter) {
            // Update novel's total word count
            $chapter->novel->updateTotalWordCount();
        });

        static::deleted(function ($chapter) {
            // Update novel's total word count when chapter is deleted
            $chapter->novel->updateTotalWordCount();
        });
    }

    public function novel()
    {
        return $this->belongsTo(Novel::class);
    }

    public function calculateWordCount(): int
    {
        if (empty($this->content)) {
            return 0;
        }

        return str_word_count(strip_tags($this->content));
    }
}
