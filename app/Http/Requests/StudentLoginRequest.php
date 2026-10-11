<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StudentLoginRequest extends FormRequest
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
            'nis' => ['required', 'string', 'max:50'],
            'full_name' => ['required', 'string', 'min:3', 'max:255'],
            'class_name' => ['required', 'string', 'max:100'],
            'exam_id' => ['required', 'integer', 'exists:exams,id'],
            'force_new_session' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nis.required' => 'Nomor Induk Siswa (NIS) wajib diisi.',
            'full_name.required' => 'Nama Lengkap siswa wajib diisi.',
            'class_name.required' => 'Kelas wajib dipilih atau diisi.',
            'exam_id.required' => 'Paket sesi ujian wajib dipilih.',
            'exam_id.exists' => 'Paket sesi ujian yang dipilih tidak valid.',
        ];
    }
}
