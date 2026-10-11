<?php

namespace App\Models;

use Database\Factories\ExamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    /** @use HasFactory<ExamFactory> */
    use HasFactory;

    public const string TIMER_MODE_PER_QUESTION = 'mode_a';

    public const string TIMER_MODE_GLOBAL = 'mode_b';

    public const int MAX_LEVEL = 15;

    /**
     * Safe Point levels on the 15-level Millionaire ladder.
     *
     * @var list<int>
     */
    public const array SAFE_LEVELS = [5, 10];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'subject',
        'timer_mode',
        'default_question_timer',
        'total_global_timer',
        'starting_score',
        'randomize_questions',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'timer_mode' => self::TIMER_MODE_PER_QUESTION,
        'default_question_timer' => 30,
        'total_global_timer' => 1200,
        'starting_score' => 20,
        'randomize_questions' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_question_timer' => 'integer',
            'total_global_timer' => 'integer',
            'starting_score' => 'integer',
            'randomize_questions' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Question, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('level_tier');
    }

    /**
     * @return HasMany<StudentSession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(StudentSession::class);
    }

    public function isPerQuestionTimer(): bool
    {
        return $this->timer_mode === self::TIMER_MODE_PER_QUESTION;
    }

    public function isGlobalTimer(): bool
    {
        return $this->timer_mode === self::TIMER_MODE_GLOBAL;
    }
}
