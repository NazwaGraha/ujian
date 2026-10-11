<?php

namespace App\Models;

use Database\Factories\StudentSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentSession extends Model
{
    /** @use HasFactory<StudentSessionFactory> */
    use HasFactory;

    public const string STATUS_ONGOING = 'ongoing';

    public const string STATUS_FINISHED = 'finished';

    public const string STATUS_ELIMINATED = 'eliminated';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'student_id',
        'exam_id',
        'session_token',
        'current_score',
        'safe_score',
        'current_step',
        'status',
        'lifeline_5050_used',
        'question_order',
        'started_at',
        'finished_at',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'current_score' => 20,
        'safe_score' => 0,
        'current_step' => 1,
        'status' => self::STATUS_ONGOING,
        'lifeline_5050_used' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'exam_id' => 'integer',
            'current_score' => 'integer',
            'safe_score' => 'integer',
            'current_step' => 'integer',
            'lifeline_5050_used' => 'boolean',
            'question_order' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
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
    public function answers(): HasMany
    {
        return $this->hasMany(StudentAnswer::class, 'session_id');
    }

    public function isOngoing(): bool
    {
        return $this->status === self::STATUS_ONGOING;
    }

    public function isEliminated(): bool
    {
        return $this->status === self::STATUS_ELIMINATED;
    }

    public function isFinished(): bool
    {
        return $this->status === self::STATUS_FINISHED;
    }

    public function remainingGlobalSeconds(): int
    {
        $totalTimer = $this->exam->total_global_timer ?? 1200;
        $startedAt = $this->started_at ?? $this->created_at ?? now();
        $elapsed = (int) max(0, $startedAt->diffInSeconds(now(), false));

        return max(0, $totalTimer - $elapsed);
    }

    public function resolveQuestionForStep(?int $step = null): ?Question
    {
        $targetStep = $step ?? $this->current_step;
        $order = $this->question_order;

        if (is_array($order) && isset($order[$targetStep - 1])) {
            return Question::query()
                ->where('exam_id', $this->exam_id)
                ->find($order[$targetStep - 1]);
        }

        return Question::query()
            ->where('exam_id', $this->exam_id)
            ->where('level_tier', $targetStep)
            ->orderBy('id')
            ->first();
    }
}
