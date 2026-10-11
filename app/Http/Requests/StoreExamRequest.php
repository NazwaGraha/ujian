<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'timer_mode' => ['required', 'string', Rule::in(['mode_a', 'mode_b'])],
            'default_question_timer' => ['required', 'integer', 'min:5', 'max:600'],
            'total_global_timer' => ['required', 'integer', 'min:60', 'max:14400'],
            'starting_score' => ['required', 'integer', 'min:5', 'max:500'],
            'randomize_questions' => ['nullable', 'boolean'],
        ];
    }
}
