<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'question_text',
        'correct_option_id',
        'points',
    ];

    /**
     * Get the exam that owns the question.
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Get the category that owns the question.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the options for the question.
     */
    public function options(): HasMany
    {
        return $this->hasMany(Option::class)->orderBy('order_by');
    }

    /**
     * Get the correct option for the question.
     */
    public function correctOption(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'correct_option_id');
    }

    /**
     * Reorder options and update their order_by values.
     */
    public function reorderOptions(array $optionIds): void
    {
        foreach ($optionIds as $index => $optionId) {
            $this->options()->where('id', $optionId)->update(['order_by' => $index + 1]);
        }
    }
}
