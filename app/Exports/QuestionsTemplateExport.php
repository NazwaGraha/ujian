<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class QuestionsTemplateExport implements FromArray, ShouldAutoSize, WithHeadings
{
    /**
     * @return array<int, mixed>
     */
    public function headings(): array
    {
        return [
            'Level_Tier',
            'Pertanyaan',
            'Opsi_A',
            'Opsi_B',
            'Opsi_C',
            'Opsi_D',
            'Kunci_Jawaban',
            'Poin_Benar',
            'Poin_Salah',
            'Durasi_Detik',
        ];
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        return [
            [1, 'Hasil dari 12 + 8 x 2 adalah...', '28', '40', '24', '32', 'A', 5, 5, 30],
            [2, 'Planet terdekat dengan Matahari di Tata Surya adalah...', 'Venus', 'Merkurius', 'Mars', 'Bumi', 'B', 5, 5, 30],
            [3, 'Lambang unsur kimia untuk Emas (Gold) adalah...', 'Ag', 'Fe', 'Au', 'Cu', 'C', 5, 5, 30],
            [4, 'Ibu kota negara Jepang adalah...', 'Seoul', 'Beijing', 'Bangkok', 'Tokyo', 'D', 5, 5, 30],
            [5, '[SAFE POINT 1] Satuan SI untuk kuat arus listrik adalah...', 'Ampere', 'Volt', 'Ohm', 'Watt', 'A', 5, 5, 30],
            [6, 'Proses tumbuhan hijau membuat makanan dengan bantuan cahaya disebut...', 'Respirasi', 'Fotosintesis', 'Transpirasi', 'Fermentasi', 'B', 5, 5, 30],
            [7, 'Nilai dari 2^5 (dua pangkat lima) adalah...', '16', '25', '32', '64', 'C', 5, 5, 30],
            [8, 'Penemu bola lampu pijar komersial adalah...', 'Nikola Tesla', 'Albert Einstein', 'Isaac Newton', 'Thomas Alva Edison', 'D', 5, 5, 30],
            [9, 'Gas yang paling banyak terdapat di atmosfer Bumi adalah...', 'Nitrogen', 'Oksigen', 'Karbon Dioksida', 'Argon', 'A', 5, 5, 30],
            [10, '[SAFE POINT 2] Hukum II Newton dirumuskan secara matematis sebagai...', 'F = m / a', 'F = m . a', 'E = m . c^2', 'P = F / A', 'B', 5, 5, 30],
            [11, 'Jika sin(x) = 1/2 untuk sudut lancip x, maka besar sudut x adalah...', '45 derajat', '60 derajat', '30 derajat', '90 derajat', 'C', 5, 5, 45],
            [12, 'Organel sel yang berfungsi sebagai pusat respirasi seluler (The Powerhouse of the Cell) adalah...', 'Ribosom', 'Lisosom', 'Badan Golgi', 'Mitokondria', 'D', 5, 5, 45],
            [13, 'Turunan pertama dari fungsi f(x) = 3x^2 - 4x + 7 adalah...', '6x - 4', '3x - 4', '6x + 7', 'x^3 - 2x^2', 'A', 5, 5, 45],
            [14, 'Protokol jaringan yang bekerja menerjemahkan nama domain menjadi alamat IP adalah...', 'HTTP', 'DNS', 'FTP', 'SSH', 'B', 5, 5, 60],
            [15, 'Berapakah determinan matriks 2x2 [[4, 2], [3, 5]]?', '10', '26', '14', '8', 'C', 10, 5, 60],
        ];
    }
}
