<?php

namespace App\Imports;

use App\Models\Exam;
use App\Models\Question;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class QuestionsImport implements SkipsEmptyRows, ToModel, WithHeadingRow, WithValidation
{
    use Importable;

    public int $importedCount = 0;

    public function __construct(
        public readonly Exam $exam,
    ) {}

    /**
     * Normalize spreadsheet headers before validation runs.
     * Supports both Indonesian column names (Level/Tier, Pertanyaan, Opsi_A..D, Kunci_Jawaban, Poin_Benar, Poin_Salah, Durasi_Detik)
     * and database column names (level_tier, question_text, opt_a..d, correct_opt, score_gain, score_penalty, duration_sec).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function prepareForValidation(array $data, int $index): array
    {
        $levelTier = $data['level_tier']
            ?? $data['leveltier']
            ?? $data['level_tier_1_15']
            ?? $data['leveltier_1_15']
            ?? $data['level']
            ?? $data['tier']
            ?? null;

        $questionText = $data['pertanyaan']
            ?? $data['question_text']
            ?? $data['soal']
            ?? null;

        $optA = $data['opsi_a'] ?? $data['opt_a'] ?? null;
        $optB = $data['opsi_b'] ?? $data['opt_b'] ?? null;
        $optC = $data['opsi_c'] ?? $data['opt_c'] ?? null;
        $optD = $data['opsi_d'] ?? $data['opt_d'] ?? null;

        $correctOpt = $data['kunci_jawaban']
            ?? $data['kunci_jawaban_abcd']
            ?? $data['kunci_jawaban_a_b_c_d']
            ?? $data['correct_opt']
            ?? null;

        $scoreGain = $data['poin_benar'] ?? $data['score_gain'] ?? 5;
        $scorePenalty = $data['poin_salah'] ?? $data['score_penalty'] ?? 5;
        $durationSec = $data['durasi_detik'] ?? $data['duration_sec'] ?? ($this->exam->default_question_timer ?? 30);

        return [
            'level_tier' => is_numeric($levelTier) ? (int) $levelTier : $levelTier,
            'pertanyaan' => is_scalar($questionText) ? trim((string) $questionText) : $questionText,
            'opsi_a' => is_scalar($optA) ? trim((string) $optA) : $optA,
            'opsi_b' => is_scalar($optB) ? trim((string) $optB) : $optB,
            'opsi_c' => is_scalar($optC) ? trim((string) $optC) : $optC,
            'opsi_d' => is_scalar($optD) ? trim((string) $optD) : $optD,
            'kunci_jawaban' => is_scalar($correctOpt) ? strtoupper(trim((string) $correctOpt)) : $correctOpt,
            'poin_benar' => ($scoreGain === null || $scoreGain === '') ? 5 : (is_numeric($scoreGain) ? (int) $scoreGain : $scoreGain),
            'poin_salah' => ($scorePenalty === null || $scorePenalty === '') ? 5 : (is_numeric($scorePenalty) ? abs((int) $scorePenalty) : $scorePenalty),
            'durasi_detik' => ($durationSec === null || $durationSec === '') ? ($this->exam->default_question_timer ?? 30) : (is_numeric($durationSec) ? (int) $durationSec : $durationSec),
        ];
    }

    /**
     * @param  array<array-key, mixed>  $row
     */
    public function model(array $row): ?Model
    {
        $this->importedCount++;

        return new Question([
            'exam_id' => $this->exam->id,
            'level_tier' => (int) $row['level_tier'],
            'question_text' => (string) $row['pertanyaan'],
            'opt_a' => (string) $row['opsi_a'],
            'opt_b' => (string) $row['opsi_b'],
            'opt_c' => (string) $row['opsi_c'],
            'opt_d' => (string) $row['opsi_d'],
            'correct_opt' => strtoupper((string) $row['kunci_jawaban']),
            'score_gain' => (int) ($row['poin_benar'] ?? 5),
            'score_penalty' => abs((int) ($row['poin_salah'] ?? 5)),
            'duration_sec' => (int) ($row['durasi_detik'] ?? $this->exam->default_question_timer ?? 30),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'level_tier' => ['required', 'integer', 'min:1', 'max:15'],
            'pertanyaan' => ['required', 'string', 'min:3'],
            'opsi_a' => ['required', 'string'],
            'opsi_b' => ['required', 'string'],
            'opsi_c' => ['required', 'string'],
            'opsi_d' => ['required', 'string'],
            'kunci_jawaban' => ['required', 'string', Rule::in(['A', 'B', 'C', 'D'])],
            'poin_benar' => ['required', 'integer', 'min:1', 'max:100'],
            'poin_salah' => ['required', 'integer', 'min:0', 'max:100'],
            'durasi_detik' => ['required', 'integer', 'min:5', 'max:3600'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function customValidationMessages(): array
    {
        return [
            'level_tier.required' => 'Kolom Level/Tier wajib diisi (1-15).',
            'level_tier.min' => 'Kolom Level/Tier minimal adalah 1.',
            'level_tier.max' => 'Kolom Level/Tier maksimal adalah 15.',
            'pertanyaan.required' => 'Kolom Pertanyaan wajib diisi.',
            'kunci_jawaban.in' => 'Kolom Kunci_Jawaban harus bernilai A, B, C, atau D.',
        ];
    }
}
