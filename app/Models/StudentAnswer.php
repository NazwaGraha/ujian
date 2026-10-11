<?php

namespace App\Models;

use Database\Factories\StudentAnswerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAnswer extends Model
{
    /** @use HasFactory<StudentAnswerFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'session_id',
        'question_id',
        'chosen_opt',
        'is_correct',
        'score_diff',
        'time_spent_sec',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'session_id' => 'integer',
            'question_id' => 'integer',
            'is_correct' => 'boolean',
            'score_diff' => 'integer',
            'time_spent_sec' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<StudentSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(StudentSession::class, 'session_id');
    }

    /**
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
