<?php

namespace App\Models;

use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'exam_id',
        'level_tier',
        'question_text',
        'opt_a',
        'opt_b',
        'opt_c',
        'opt_d',
        'correct_opt',
        'score_gain',
        'score_penalty',
        'duration_sec',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'score_gain' => 5,
        'score_penalty' => 5,
        'duration_sec' => 30,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'exam_id' => 'integer',
            'level_tier' => 'integer',
            'score_gain' => 'integer',
            'score_penalty' => 'integer',
            'duration_sec' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Exam, $this>
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * @return HasMany<StudentAnswer, $this>
     */
    public function studentAnswers(): HasMany
    {
        return $this->hasMany(StudentAnswer::class);
    }

    public function isSafePoint(): bool
    {
        return in_array($this->level_tier, Exam::SAFE_LEVELS, true);
    }

    /**
     * Format question payload for the frontend arena without exposing correct_opt.
     *
     * @return array{
     *     id: int,
     *     level_tier: int,
     *     question_text: string,
     *     options: array{A: string, B: string, C: string, D: string},
     *     score_gain: int,
     *     score_penalty: int,
     *     duration_sec: int,
     *     is_safe_point: bool
     * }
     */
    public function toArenaPayload(?int $fallbackDuration = null): array
    {
        return [
            'id' => $this->id,
            'level_tier' => $this->level_tier,
            'question_text' => $this->question_text,
            'options' => [
                'A' => $this->opt_a,
                'B' => $this->opt_b,
                'C' => $this->opt_c,
                'D' => $this->opt_d,
            ],
            'score_gain' => $this->score_gain,
            'score_penalty' => $this->score_penalty,
            'duration_sec' => $this->duration_sec > 0 ? $this->duration_sec : ($fallbackDuration ?? 30),
            'is_safe_point' => $this->isSafePoint(),
        ];
    }
}
