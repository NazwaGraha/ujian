<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Ujian Online') }} — Portal Peserta CBT Millionaire</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    @endif

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    <style>
        .millionaire-bg {
            background-color: #070b19;
            background-image:
                radial-gradient(circle at 50% 10%, rgba(56, 189, 248, 0.18) 0%, rgba(15, 23, 42, 0.88) 45%, #070b19 90%),
                radial-gradient(circle at 85% 85%, rgba(245, 158, 11, 0.1) 0%, transparent 50%);
        }
    </style>
</head>
<body class="millionaire-bg min-h-screen text-slate-100 antialiased">

<div
    class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8"
    x-data="{
        keyBuffer: '',
        handleGlobalKey(e) {
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) {
                return;
            }
            if (e.key && e.key.length === 1) {
                this.keyBuffer = (this.keyBuffer + e.key.toLowerCase()).slice(-5);
                if (this.keyBuffer === 'login') {
                    window.location.href = '{{ route('login') }}';
                }
            }
        }
    }"
    @keydown.window="handleGlobalKey($event)"
>
    <!-- Top Navigation -->
    <nav class="flex flex-wrap items-center justify-between gap-4 pb-6 border-b border-sky-500/20">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-sky-400 to-blue-700 flex items-center justify-center font-black text-xl text-slate-950 shadow-[0_0_25px_rgba(56,189,248,0.5)]">
                ◆
            </div>
            <div>
                <span class="text-xs font-black uppercase tracking-widest text-sky-400">GAMIFIED COMPUTER-BASED TEST</span>
                <h1 class="text-xl sm:text-2xl font-black tracking-tight text-white">
                    {{ config('app.name', 'Ujian Online') }}
                </h1>
            </div>
        </div>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mt-8 items-start">

        <!-- FORM LOGIN & REGISTRASI SISWA (7 Kolom) -->
        <section class="lg:col-span-7 bg-[#0f172a]/90 border-2 border-sky-400/50 rounded-3xl p-6 sm:p-8 shadow-[0_0_40px_rgba(56,189,248,0.15)]">
            <div class="flex items-center justify-between border-b border-slate-800 pb-4 mb-6">
                <div>
                    <h2 class="text-xl font-black text-white">Registrasi &amp; Login Sesi Siswa</h2>
                    <p class="text-xs text-slate-400 mt-1">Masukkan identitas NIS, Nama Lengkap, dan Kelas untuk memulai panggung kuis.</p>
                </div>
                <span class="px-3 py-1.5 rounded-xl bg-amber-500/20 border border-amber-400/50 text-amber-300 text-xs font-black">
                    Modal Awal: +20 Poin
                </span>
            </div>

            @if ($errors->any())
                <div class="mb-6 p-4 rounded-2xl bg-rose-950/80 border border-rose-500/50 text-rose-200 text-xs space-y-1">
                    @foreach ($errors->all() as $error)
                        <div>• {{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form
                action="{{ route('exam.start') }}"
                method="POST"
                class="space-y-5"
                x-data="{ customClass: false, nisVal: '{{ old('nis') }}' }"
                @submit="if (nisVal.trim().toLowerCase() === 'login') { $event.preventDefault(); window.location.href = '{{ route('login') }}'; }"
            >
                @csrf

                <!-- NIS -->
                <div>
                    <label for="nis" class="block text-xs font-bold uppercase tracking-wider text-sky-300 mb-2">
                        Nomor Induk Siswa (NIS)
                    </label>
                    <input
                        type="text"
                        id="nis"
                        name="nis"
                        x-model="nisVal"
                        @input="if (nisVal.trim().toLowerCase() === 'login') { window.location.href = '{{ route('login') }}'; }"
                        @keydown.enter="if (nisVal.trim().toLowerCase() === 'login') { $event.preventDefault(); window.location.href = '{{ route('login') }}'; }"
                        required
                        placeholder="Contoh: 20261001"
                        class="w-full px-4 py-3 rounded-xl bg-[#070b19] border border-sky-500/40 focus:border-amber-400 focus:outline-none text-white text-sm font-semibold"
                    >
                </div>

                <!-- Nama Lengkap -->
                <div>
                    <label for="full_name" class="block text-xs font-bold uppercase tracking-wider text-sky-300 mb-2">
                        Nama Lengkap Siswa
                    </label>
                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        value="{{ old('full_name') }}"
                        required
                        placeholder="Masukkan nama lengkap sesuai absen"
                        class="w-full px-4 py-3 rounded-xl bg-[#070b19] border border-sky-500/40 focus:border-amber-400 focus:outline-none text-white text-sm font-semibold"
                    >
                </div>

                <!-- Kelas (Dropdown / Input Manual) -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label for="class_name" class="block text-xs font-bold uppercase tracking-wider text-sky-300">
                            Kelas
                        </label>
                        <button
                            type="button"
                            @click="customClass = !customClass"
                            class="text-[11px] text-amber-400 hover:underline font-semibold cursor-pointer"
                            x-text="customClass ? 'Pilih dari Daftar Kelas' : 'Ketik Kelas Manual'"
                        ></button>
                    </div>

                    <template x-if="!customClass">
                        <select
                            id="class_name"
                            name="class_name"
                            required
                            class="w-full px-4 py-3 rounded-xl bg-[#070b19] border border-sky-500/40 focus:border-amber-400 focus:outline-none text-white text-sm font-semibold"
                        >
                            <option value="">-- Pilih Kelas --</option>
                            @foreach (['X MIPA 1', 'X MIPA 2', 'XI MIPA 1', 'XI MIPA 2', 'XII MIPA 1', 'XII MIPA 2', 'XII IPS 1', 'XII IPS 2'] as $classOption)
                                <option value="{{ $classOption }}" @selected(old('class_name') === $classOption)>{{ $classOption }}</option>
                            @endforeach
                        </select>
                    </template>

                    <template x-if="customClass">
                        <input
                            type="text"
                            name="class_name"
                            value="{{ old('class_name') }}"
                            required
                            placeholder="Ketik nama kelas, misal: XII RPL 1"
                            class="w-full px-4 py-3 rounded-xl bg-[#070b19] border border-sky-500/40 focus:border-amber-400 focus:outline-none text-white text-sm font-semibold"
                        >
                    </template>
                </div>

                <!-- Pilih Paket Sesi Ujian -->
                <div>
                    <label for="exam_id" class="block text-xs font-bold uppercase tracking-wider text-sky-300 mb-2">
                        Pilih Sesi / Paket Ujian
                    </label>
                    <select
                        id="exam_id"
                        name="exam_id"
                        required
                        class="w-full px-4 py-3 rounded-xl bg-[#070b19] border border-sky-500/40 focus:border-amber-400 focus:outline-none text-white text-sm font-semibold"
                    >
                        @forelse ($exams as $examItem)
                            <option value="{{ $examItem->id }}" @selected((int) old('exam_id') === $examItem->id)>
                                {{ $examItem->title }} ({{ $examItem->subject }}) —
                                {{ $examItem->timer_mode === 'mode_a' ? "Mode A: {$examItem->default_question_timer}d/Soal" : 'Mode B: '.round($examItem->total_global_timer / 60).' Menit Global' }}
                                [{{ $examItem->questions_count }} Soal]
                            </option>
                        @empty
                            <option value="">Belum ada paket ujian tersedia</option>
                        @endforelse
                    </select>
                </div>

                <!-- Opsi Mulai Sesi Baru -->
                <div class="flex items-center gap-2.5 pt-1">
                    <input
                        type="checkbox"
                        id="force_new_session"
                        name="force_new_session"
                        value="1"
                        class="w-4 h-4 rounded border-sky-500/50 bg-[#070b19] text-amber-400"
                    >
                    <label for="force_new_session" class="text-xs text-slate-300">
                        Mulai sesi baru dari Level 1 (reset sesi lama jika masih berjalan)
                    </label>
                </div>

                <button
                    type="submit"
                    class="w-full py-4 rounded-2xl bg-gradient-to-r from-amber-500 via-amber-400 to-yellow-400 hover:brightness-110 text-slate-950 font-black text-base uppercase tracking-wider shadow-[0_0_30px_rgba(245,158,11,0.5)] transition cursor-pointer"
                >
                    Masuk ke Panggung Ujian &gt;
                </button>
            </form>
        </section>

        <!-- ATURAN PERMAINAN & LEADERBOARD (5 Kolom) -->
        <aside class="lg:col-span-5 space-y-6">
            <!-- Kartu Aturan Millionaire CBT -->
            <div class="bg-[#0f172a]/90 border border-sky-400/40 rounded-3xl p-6 shadow-[0_0_30px_rgba(7,11,25,0.8)]">
                <h3 class="text-sm font-black uppercase tracking-wider text-amber-400 mb-3">
                    ★ Aturan Panggung Millionaire CBT
                </h3>
                <ul class="space-y-2.5 text-xs text-slate-300 leading-relaxed">
                    <li class="flex items-start gap-2">
                        <span class="text-sky-400 font-black">◆</span>
                        <span><strong>Modal Awal 20 Poin:</strong> Setiap peserta memulai panggung kuis dengan modal 20 poin.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-emerald-400 font-black">◆</span>
                        <span><strong>Jawaban Benar (+5 Poin):</strong> Menambah skor sesuai bobot soal dan naik ke level berikutnya.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-rose-400 font-black">◆</span>
                        <span><strong>Jawaban Salah / Timeout (-5 Poin):</strong> Mengurangi skor sebanyak 5 poin.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-rose-500 font-black">◆</span>
                        <span><strong>Eliminasi (Game Over = 0):</strong> Jika poin menyentuh <strong>0</strong>, sesi otomatis berakhir (Disqualified).</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-amber-400 font-black">◆</span>
                        <span><strong>Safe Point [AMAN]:</strong> Nilai dasar terkunci saat berhasil menaklukkan <strong>Level 5</strong> dan <strong>Level 10</strong>.</span>
                    </li>
                </ul>
            </div>

            <!-- Papan Skor (Leaderboard) -->
            <div class="bg-[#0f172a]/90 border border-sky-400/40 rounded-3xl p-6">
                <h3 class="text-sm font-black uppercase tracking-wider text-sky-300 mb-4">
                    🏆 Papan Skor Sesi Terbaru
                </h3>

                <div class="space-y-2.5">
                    @forelse ($leaderboard as $entry)
                        <div class="flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-slate-900/90 border border-slate-800 text-xs">
                            <div>
                                <div class="font-bold text-white">{{ $entry->student->full_name }}</div>
                                <div class="text-[11px] text-slate-400">{{ $entry->student->nis }} • {{ $entry->student->class_name }}</div>
                            </div>
                            <div class="text-right">
                                <div class="font-black text-sm {{ $entry->status === 'eliminated' ? 'text-rose-400' : 'text-amber-400' }}">
                                    {{ $entry->current_score }} Poin
                                </div>
                                <div class="text-[10px] uppercase tracking-wider {{ $entry->status === 'eliminated' ? 'text-rose-400' : 'text-emerald-400' }}">
                                    {{ $entry->status }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 text-center py-4">Belum ada riwayat sesi ujian.</p>
                    @endforelse
                </div>
            </div>
        </aside>
    </div>
</div>

</body>
</html>
