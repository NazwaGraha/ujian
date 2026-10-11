<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('questions') && $this->has('question_text')) {
            $this->merge([
                'questions' => [
                    [
                        'level_tier' => $this->input('level_tier', 1),
                        'question_text' => $this->input('question_text'),
                        'opt_a' => $this->input('opt_a'),
                        'opt_b' => $this->input('opt_b'),
                        'opt_c' => $this->input('opt_c'),
                        'opt_d' => $this->input('opt_d'),
                        'correct_opt' => is_string($this->input('correct_opt')) ? strtoupper(trim($this->input('correct_opt'))) : $this->input('correct_opt'),
                        'score_gain' => $this->input('score_gain', 5),
                        'score_penalty' => $this->input('score_penalty', 5),
                        'duration_sec' => $this->input('duration_sec', 30),
                    ],
                ],
            ]);
        } elseif (is_array($this->input('questions'))) {
            $normalized = [];
            foreach ($this->input('questions') as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $normalized[] = [
                    'level_tier' => $row['level_tier'] ?? 1,
                    'question_text' => $row['question_text'] ?? null,
                    'opt_a' => $row['opt_a'] ?? null,
                    'opt_b' => $row['opt_b'] ?? null,
                    'opt_c' => $row['opt_c'] ?? null,
                    'opt_d' => $row['opt_d'] ?? null,
                    'correct_opt' => isset($row['correct_opt']) && is_string($row['correct_opt'])
                        ? strtoupper(trim($row['correct_opt']))
                        : ($row['correct_opt'] ?? 'A'),
                    'score_gain' => $row['score_gain'] ?? 5,
                    'score_penalty' => isset($row['score_penalty']) ? abs((int) $row['score_penalty']) : 5,
                    'duration_sec' => $row['duration_sec'] ?? 30,
                ];
            }
            $this->merge(['questions' => $normalized]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'exam_id' => ['required', 'integer', 'exists:exams,id'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.level_tier' => ['required', 'integer', 'min:1', 'max:15'],
            'questions.*.question_text' => ['required', 'string', 'min:3'],
            'questions.*.opt_a' => ['required', 'string'],
            'questions.*.opt_b' => ['required', 'string'],
            'questions.*.opt_c' => ['required', 'string'],
            'questions.*.opt_d' => ['required', 'string'],
            'questions.*.correct_opt' => ['required', 'string', \Illuminate\Validation\Rule::in(['A', 'B', 'C', 'D'])],
            'questions.*.score_gain' => ['required', 'integer', 'min:1', 'max:100'],
            'questions.*.score_penalty' => ['required', 'integer', 'min:0', 'max:100'],
            'questions.*.duration_sec' => ['required', 'integer', 'min:5', 'max:3600'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'exam_id.required' => 'Pilih paket ujian tujuan terlebih dahulu.',
            'questions.required' => 'Data soal wajib diisi.',
            'questions.*.level_tier.required' => 'Level/Tier soal (1-15) wajib diisi.',
            'questions.*.question_text.required' => 'Teks pertanyaan wajib diisi.',
            'questions.*.opt_a.required' => 'Opsi A wajib diisi.',
            'questions.*.opt_b.required' => 'Opsi B wajib diisi.',
            'questions.*.opt_c.required' => 'Opsi C wajib diisi.',
            'questions.*.opt_d.required' => 'Opsi D wajib diisi.',
            'questions.*.correct_opt.in' => 'Kunci jawaban harus A, B, C, atau D.',
        ];
    }
}
