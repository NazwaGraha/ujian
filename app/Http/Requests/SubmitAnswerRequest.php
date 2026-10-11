<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitAnswerRequest extends FormRequest
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
        if ($this->has('chosen_opt') && is_string($this->input('chosen_opt')) && $this->input('chosen_opt') !== '') {
            $this->merge([
                'chosen_opt' => strtoupper(trim($this->input('chosen_opt'))),
            ]);
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
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'chosen_opt' => ['nullable', 'string', Rule::in(['A', 'B', 'C', 'D'])],
            'time_spent_sec' => ['nullable', 'integer', 'min:0', 'max:7200'],
            'is_timeout' => ['nullable', 'boolean'],
            'is_global_timeout' => ['nullable', 'boolean'],
        ];
    }
}
