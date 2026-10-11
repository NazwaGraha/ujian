<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Ujian Online') }} — Panel Guru &amp; Administrator CBT</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    @endif

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body class="bg-[#070b19] min-h-screen text-slate-100 antialiased">

@php
    $currentUser = auth()->user();
    $canDelete = $currentUser?->canDeleteData() ?? false;
@endphp

<div
    class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8"
    x-data="{
        examFormMode: 'edit',
        editingQuestion: null,
        editingUser: null,
        openEditModal(q) {
            this.editingQuestion = { ...q };
        },
        closeEditModal() {
            this.editingQuestion = null;
        },
        openEditUserModal(u) {
            this.editingUser = { ...u, password: '' };
        },
        closeEditUserModal() {
            this.editingUser = null;
        }
    }"
>
    <!-- Header -->
    <header class="flex flex-wrap items-center justify-between gap-4 pb-6 border-b border-sky-500/20">
        <div>
            <div class="flex flex-wrap items-center gap-2.5">
                <span class="text-xs font-black uppercase tracking-widest text-sky-400">PANEL GURU &amp; ADMINISTRATOR CBT</span>
                @if ($currentUser)
                    @if ($currentUser->isSupervisor())
                        <span class="px-2.5 py-0.5 rounded-full bg-amber-500/20 border border-amber-400/60 text-amber-300 text-[11px] font-black uppercase tracking-wider">
                            👑 {{ $currentUser->name }} (@{{ $currentUser->username }}) — SUPERVISOR (FULL AKSES)
                        </span>
                    @else
                        <span class="px-2.5 py-0.5 rounded-full bg-sky-500/20 border border-sky-400/60 text-sky-300 text-[11px] font-black uppercase tracking-wider">
                            🛡️ {{ $currentUser->name }} (@{{ $currentUser->username }}) — ADMIN (TANPA DELETE DATA)
                        </span>
                    @endif
                @endif
            </div>
            <h1 class="text-2xl font-black text-white mt-1">Manajemen Sesi Waktu, Input Bank Soal, Rekap Nilai &amp; Backoffice User</h1>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a
                href="#section-backoffice-users"
                class="px-3.5 py-2.5 rounded-xl bg-cyan-500/20 hover:bg-cyan-500/30 border border-cyan-400/50 text-cyan-200 text-xs font-extrabold uppercase tracking-wider transition"
            >
                👥 Backoffice User &amp; Guru
            </a>
            <a
                href="#section-input-soal"
                class="px-3.5 py-2.5 rounded-xl bg-amber-500/20 hover:bg-amber-500/30 border border-amber-400/50 text-amber-300 text-xs font-extrabold uppercase tracking-wider transition"
            >
                ✍️ + Input Soal Manual
            </a>
            <a
                href="#section-rekap-peserta"
                class="px-3.5 py-2.5 rounded-xl bg-purple-500/20 hover:bg-purple-500/30 border border-purple-400/50 text-purple-300 text-xs font-extrabold uppercase tracking-wider transition"
            >
                📊 Tabel Peserta &amp; Nilai
            </a>
            <a
                href="{{ route('teacher.questions.template') }}"
                class="px-3.5 py-2.5 rounded-xl bg-emerald-500/20 hover:bg-emerald-500/30 border border-emerald-400/50 text-emerald-300 text-xs font-extrabold uppercase tracking-wider transition"
            >
                📥 Template Excel
            </a>
            <a
                href="{{ route('exam.login') }}"
                class="px-3.5 py-2.5 rounded-xl bg-sky-500 hover:bg-sky-400 text-slate-950 text-xs font-black uppercase tracking-wider transition"
            >
                ← Portal Siswa
            </a>
            <form action="{{ route('logout') }}" method="POST" class="inline">
                @csrf
                <button
                    type="submit"
                    class="px-3.5 py-2.5 rounded-xl bg-rose-500/20 hover:bg-rose-500/30 border border-rose-400/50 text-rose-300 text-xs font-black uppercase tracking-wider transition cursor-pointer"
                >
                    🚪 Logout
                </button>
            </form>
        </div>
    </header>

    @if (session('success'))
        <div class="p-4 rounded-2xl bg-emerald-950/90 border border-emerald-400/60 text-emerald-200 text-sm font-bold">
            ✓ {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="p-4 rounded-2xl bg-rose-950/90 border border-rose-400/60 text-rose-200 text-sm space-y-1">
            @foreach ($errors->all() as $error)
                <div>• {{ $error }}</div>
            @endforeach
        </div>
    @endif

    <!-- Navigasi Cepat Pilih Paket / Sesi Ujian Aktif -->
    <div class="bg-[#0f172a]/90 border border-sky-500/30 rounded-2xl p-4 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <span class="text-xs font-black uppercase tracking-wider text-sky-400">Paket / Sesi Ujian Aktif:</span>
            <div class="flex flex-wrap gap-2">
                @foreach ($exams as $examTab)
                    <a
                        href="{{ route('teacher.exams', ['exam_id' => $examTab->id]) }}"
                        class="px-3.5 py-2 rounded-xl text-xs font-bold border transition {{ $selectedExam && $selectedExam->id === $examTab->id ? 'bg-sky-500 text-slate-950 border-sky-400 shadow-[0_0_15px_rgba(56,189,248,0.4)]' : 'bg-slate-900 text-slate-300 border-slate-700 hover:border-sky-400/50' }}"
                    >
                        {{ $examTab->title }} ({{ $examTab->subject }}) — {{ $examTab->questions_count }} Soal
                    </a>
                @endforeach
            </div>
        </div>

        <button
            type="button"
            @click="examFormMode = examFormMode === 'create' ? 'edit' : 'create'"
            class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition cursor-pointer"
            :class="examFormMode === 'create' ? 'bg-slate-800 text-slate-200 border border-slate-600' : 'bg-emerald-500 hover:bg-emerald-400 text-slate-950'"
        >
            <span x-text="examFormMode === 'create' ? '← Kembali Edit Paket Aktif' : '+ Buat Sesi / Paket Ujian Baru'"></span>
        </button>
    </div>

    <!-- Grid Utama: Konfigurasi Mode Waktu & Impor Spreadsheet Excel -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Kolom 1: Pengaturan Sesi Ujian & Mode Waktu (6 Kolom) -->
        <section class="lg:col-span-6 bg-[#0f172a]/90 border border-sky-400/40 rounded-3xl p-6 space-y-5">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h2 class="text-base font-black text-sky-300 uppercase tracking-wider">
                    <span x-show="examFormMode === 'edit'">1. Konfigurasi Paket &amp; Mode Waktu Ujian</span>
                    <span x-show="examFormMode === 'create'" style="display: none;" class="text-emerald-300">1. Buat Sesi / Paket Ujian Baru</span>
                </h2>
                @if ($selectedExam)
                    <span x-show="examFormMode === 'edit'" class="text-xs px-2.5 py-1 rounded bg-slate-800 text-amber-300 font-bold">
                        ID Paket #{{ $selectedExam->id }}
                    </span>
                @endif
            </div>

            <!-- Form Edit Paket Aktif -->
            @if ($selectedExam)
                <div x-show="examFormMode === 'edit'">
                    <form action="{{ route('teacher.exams.update', $selectedExam) }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-300 mb-1">Judul Paket / Sesi Ujian</label>
                                <input type="text" name="title" value="{{ $selectedExam->title }}" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#070b19] border border-slate-700 text-sm text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-300 mb-1">Mata Pelajaran</label>
                                <input type="text" name="subject" value="{{ $selectedExam->subject }}" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#070b19] border border-slate-700 text-sm text-white">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-amber-300 mb-1">Mode Waktu</label>
                                <select name="timer_mode" class="w-full px-3.5 py-2.5 rounded-xl bg-[#070b19] border border-sky-500/50 text-sm text-white font-semibold">
                                    <option value="mode_a" @selected($selectedExam->timer_mode === 'mode_a')>Mode A (Timer Per Soal)</option>
                                    <option value="mode_b" @selected($selectedExam->timer_mode === 'mode_b')>Mode B (Timer Global)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-300 mb-1">Timer/Soal (Detik)</label>
                                <input type="number" name="default_question_timer" value="{{ $selectedExam->default_question_timer }}" min="5" max="600" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#070b19] border border-slate-700 text-sm text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-300 mb-1">Timer Global (Detik)</label>
                                <input type="number" name="total_global_timer" value="{{ $selectedExam->total_global_timer }}" min="60" max="14400" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#070b19] border border-slate-700 text-sm text-white">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
                            <div>
                                <label class="block text-xs font-bold text-slate-300 mb-1">Modal Poin Awal Siswa</label>
                                <input type="number" name="starting_score" value="{{ $selectedExam->starting_score }}" min="5" max="500" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#070b19] border border-slate-700 text-sm text-white">
                            </div>
                            <div class="flex items-center gap-2.5 pt-5">
                                <input type="checkbox" id="randomize_questions" name="randomize_questions" value="1" @checked($selectedExam->randomize_questions) class="w-4 h-4 rounded">
                                <label for="randomize_questions" class="text-xs text-slate-300 font-semibold">
                                    Acak soal per tingkat kesulitan (Level 1-15)
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="w-full py-3 rounded-xl bg-sky-500 hover:bg-sky-400 text-slate-950 font-black text-xs uppercase tracking-wider transition cursor-pointer">
                            Simpan Konfigurasi Paket Ujian
                        </button>
                    </form>

                    @if ($exams->count() > 1)
                        @if ($canDelete)
                            <form action="{{ route('teacher.exams.destroy', $selectedExam) }}" method="POST" class="mt-3" onsubmit="return confirm('Hapus paket ujian ini beserta seluruh soal di dalamnya?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full py-2 rounded-xl bg-rose-950/70 hover:bg-rose-900/80 border border-rose-500/40 text-rose-300 font-bold text-xs uppercase tracking-wider transition cursor-pointer">
                                    🗑️ Hapus Paket Ujian Ini
                                </button>
                            </form>
                        @else
                            <div class="mt-3 py-2 px-3 rounded-xl bg-slate-900/80 border border-slate-700/80 text-slate-400 font-bold text-[11px] text-center">
                                🔒 Fitur Hapus Paket Ujian Dinonaktifkan (Level Admin tidak dapat menghapus data)
                            </div>
                        @endif
                    @endif
                </div>
            @endif

            <!-- Form Buat Sesi / Paket Ujian Baru -->
            <form
                x-show="examFormMode === 'create'"
                style="display: none;"
                action="{{ route('teacher.exams.store') }}"
                method="POST"
                class="space-y-4"
            >
                @csrf
                <p class="text-xs text-emerald-300 bg-emerald-950/50 border border-emerald-500/30 rounded-xl p-3">
                    Paket/Sesi ujian yang dibuat di sini akan otomatis muncul di halaman <strong>Registrasi &amp; Login Sesi Siswa</strong>.
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Judul Paket / Sesi Ujian Baru</label>
                        <input type="text" name="title" placeholder="Contoh: Ujian Akhir Semester Genap" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#070b19] border border-emerald-500/50 text-sm text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Mata Pelajaran</label>
                        <input type="text" name="subject" placeholder="Contoh: Bahasa Indonesia / Biologi" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#070b19] border border-emerald-500/50 text-sm text-white">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-amber-300 mb-1">Mode Waktu</label>
                        <select name="timer_mode" class="w-full px-3.5 py-2.5 rounded-xl bg-[#070b19] border border-sky-500/50 text-sm text-white font-semibold">
                            <option value="mode_a">Mode A (Timer Per Soal)</option>
                            <option value="mode_b">Mode B (Timer Global)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Timer/Soal (Detik)</label>
                        <input type="number" name="default_question_timer" value="30" min="5" max="600" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#070b19] border border-slate-700 text-sm text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Timer Global (Detik)</label>
                        <input type="number" name="total_global_timer" value="1200" min="60" max="14400" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#070b19] border border-slate-700 text-sm text-white">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Modal Poin Awal Siswa</label>
                        <input type="number" name="starting_score" value="20" min="5" max="500" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#070b19] border border-slate-700 text-sm text-white">
                    </div>
                    <div class="flex items-center gap-2.5 pt-5">
                        <input type="checkbox" id="new_randomize_questions" name="randomize_questions" value="1" checked class="w-4 h-4 rounded">
                        <label for="new_randomize_questions" class="text-xs text-slate-300 font-semibold">
                            Acak soal per tingkat kesulitan (Level 1-15)
                        </label>
                    </div>
                </div>

                <button type="submit" class="w-full py-3 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs uppercase tracking-wider transition cursor-pointer">
                    + Simpan &amp; Buat Paket Ujian Baru
                </button>
            </form>
        </section>

        <!-- Kolom 2: Impor Bank Soal dari Excel (.xlsx) via maatwebsite/excel (6 Kolom) -->
        <section class="lg:col-span-6 bg-[#0f172a]/90 border border-amber-400/40 rounded-3xl p-6 space-y-5">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h2 class="text-base font-black text-amber-300 uppercase tracking-wider">
                    2. Impor Bank Soal Spreadsheet (.xlsx / .csv)
                </h2>
                <span class="text-xs text-slate-400">Maatwebsite Laravel Excel</span>
            </div>

            <form action="{{ route('teacher.questions.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Pilih Paket Ujian Tujuan</label>
                    <select name="exam_id" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#070b19] border border-slate-700 text-sm text-white font-semibold">
                        @foreach ($exams as $examItem)
                            <option value="{{ $examItem->id }}" @selected($selectedExam && $selectedExam->id === $examItem->id)>
                                {{ $examItem->title }} ({{ $examItem->questions_count }} soal)
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">File Spreadsheet (.xlsx, .xls, .csv)</label>
                    <input
                        type="file"
                        name="file"
                        accept=".xlsx,.xls,.csv"
                        required
                        class="w-full px-3.5 py-2.5 rounded-xl bg-[#070b19] border border-amber-400/50 text-sm text-slate-200 file:mr-3 file:px-3 file:py-1.5 file:rounded-lg file:border-0 file:bg-amber-400 file:text-slate-950 file:font-bold"
                    >
                    <p class="text-[11px] text-slate-400 mt-1.5">
                        Kolom wajib: <code>Level_Tier (1-15)</code>, <code>Pertanyaan</code>, <code>Opsi_A</code>, <code>Opsi_B</code>, <code>Opsi_C</code>, <code>Opsi_D</code>, <code>Kunci_Jawaban (A/B/C/D)</code>, <code>Poin_Benar</code>, <code>Poin_Salah</code>, <code>Durasi_Detik</code>.
                    </p>
                </div>

                @if ($canDelete)
                    <div class="flex items-center gap-2.5">
                        <input type="checkbox" id="replace_existing" name="replace_existing" value="1" class="w-4 h-4 rounded">
                        <label for="replace_existing" class="text-xs text-slate-300">
                            Ganti (timpa) seluruh soal lama pada paket ujian ini
                        </label>
                    </div>
                @else
                    <div class="flex items-center gap-2 text-xs text-slate-500">
                        <span>🔒 Opsi hapus/timpa soal lama dikunci untuk Level Admin (impor akan menambahkan soal baru).</span>
                    </div>
                @endif

                <button type="submit" class="w-full py-3 rounded-xl bg-gradient-to-r from-amber-500 to-yellow-400 hover:brightness-110 text-slate-950 font-black text-xs uppercase tracking-wider transition cursor-pointer">
                    📤 Impor Soal Sekarang
                </button>
            </form>
        </section>
    </div>

    <!-- SECTION 3: FORM INPUT BERBAGAI MACAM SOAL MANUAL KE DATABASE -->
    <section
        id="section-input-soal"
        class="bg-[#0f172a]/90 border-2 border-emerald-400/40 rounded-3xl p-6 space-y-5 shadow-[0_0_30px_rgba(16,185,129,0.1)]"
        x-data="{
            rows: [
                {
                    level_tier: 1,
                    question_text: '',
                    opt_a: '',
                    opt_b: '',
                    opt_c: '',
                    opt_d: '',
                    correct_opt: 'A',
                    score_gain: 5,
                    score_penalty: 5,
                    duration_sec: {{ $selectedExam?->default_question_timer ?? 30 }}
                }
            ],
            addRow() {
                const lastRow = this.rows[this.rows.length - 1];
                const nextLevel = lastRow ? Math.min(15, parseInt(lastRow.level_tier || 1) + 1) : 1;
                this.rows.push({
                    level_tier: nextLevel,
                    question_text: '',
                    opt_a: '',
                    opt_b: '',
                    opt_c: '',
                    opt_d: '',
                    correct_opt: 'A',
                    score_gain: lastRow ? lastRow.score_gain : 5,
                    score_penalty: lastRow ? lastRow.score_penalty : 5,
                    duration_sec: lastRow ? lastRow.duration_sec : {{ $selectedExam?->default_question_timer ?? 30 }}
                });
            },
            removeRow(index) {
                if (this.rows.length > 1) {
                    this.rows.splice(index, 1);
                }
            }
        }"
    >
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-800 pb-4">
            <div>
                <span class="text-xs font-black uppercase tracking-widest text-emerald-400">INPUT BANK SOAL LANGSUNG KE DATABASE</span>
                <h2 class="text-lg font-black text-white mt-0.5">
                    3. Form Input Berbagai Macam Soal Ujian (Multi-Soal &amp; Multi-Level)
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">
                    Tambahkan satu atau banyak soal sekaligus untuk paket ujian yang dipilih. Soal yang disimpan otomatis aktif di Portal Siswa.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="addRow()"
                    class="px-4 py-2.5 rounded-xl bg-emerald-500/20 hover:bg-emerald-500/30 border border-emerald-400/60 text-emerald-300 text-xs font-black uppercase tracking-wider transition cursor-pointer"
                >
                    + Tambah Baris Soal (<span x-text="rows.length"></span>)
                </button>
            </div>
        </div>

        <form action="{{ route('teacher.questions.store') }}" method="POST" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center bg-[#070b19] p-4 rounded-2xl border border-slate-800">
                <div class="md:col-span-6">
                    <label class="block text-xs font-bold uppercase tracking-wider text-sky-300 mb-1.5">
                        Simpan Soal ke Sesi / Paket Ujian:
                    </label>
                    <select name="exam_id" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#0f172a] border border-sky-500/50 text-sm text-white font-bold">
                        @foreach ($exams as $examItem)
                            <option value="{{ $examItem->id }}" @selected($selectedExam && $selectedExam->id === $examItem->id)>
                                {{ $examItem->title }} ({{ $examItem->subject }}) — Saat ini: {{ $examItem->questions_count }} soal
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-6 flex flex-wrap items-center justify-between gap-3 pt-2 md:pt-5">
                    @if ($canDelete)
                        <label class="flex items-center gap-2.5 text-xs text-slate-300 cursor-pointer">
                            <input type="checkbox" name="replace_existing" value="1" class="w-4 h-4 rounded">
                            <span>Hapus &amp; ganti seluruh soal lama pada paket ini dengan daftar soal baru di bawah</span>
                        </label>
                    @else
                        <span class="text-xs text-slate-500">
                            🔒 Opsi hapus/timpa soal lama dikunci untuk Level Admin (soal baru akan ditambahkan ke database).
                        </span>
                    @endif
                </div>
            </div>

            <!-- Daftar Baris Input Soal -->
            <div class="space-y-4">
                <template x-for="(row, index) in rows" :key="index">
                    <div class="p-5 rounded-2xl bg-[#070b19]/90 border border-slate-700/80 hover:border-emerald-400/50 transition space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-800/80 pb-3">
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1 rounded-lg bg-emerald-500/20 border border-emerald-400/40 text-emerald-300 text-xs font-black">
                                    Soal #<span x-text="index + 1"></span>
                                </span>
                                <span
                                    x-show="parseInt(row.level_tier) === 5 || parseInt(row.level_tier) === 10"
                                    class="px-2.5 py-1 rounded-lg bg-amber-500/20 border border-amber-400/50 text-amber-300 text-[11px] font-black"
                                >
                                    ★ LEVEL TITIK AMAN [SAFE POINT]
                                </span>
                            </div>

                            <button
                                type="button"
                                x-show="rows.length > 1"
                                @click="removeRow(index)"
                                class="px-3 py-1 rounded-lg bg-rose-500/20 hover:bg-rose-500/30 border border-rose-500/40 text-rose-300 text-xs font-bold cursor-pointer"
                            >
                                ✕ Hapus Baris
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                            <!-- Level Tier -->
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-sky-300 mb-1">Level / Tier (1-15)</label>
                                <select
                                    :name="`questions[${index}][level_tier]`"
                                    x-model="row.level_tier"
                                    required
                                    class="w-full px-3 py-2.5 rounded-xl bg-[#0f172a] border border-slate-700 text-sm text-white font-bold"
                                >
                                    @for ($lvl = 1; $lvl <= 15; $lvl++)
                                        <option value="{{ $lvl }}">
                                            Level {{ $lvl }} {{ in_array($lvl, [5, 10]) ? '★ [AMAN]' : '' }}
                                        </option>
                                    @endfor
                                </select>
                            </div>

                            <!-- Teks Pertanyaan -->
                            <div class="md:col-span-10">
                                <label class="block text-xs font-bold text-slate-200 mb-1">Teks Pertanyaan Soal</label>
                                <input
                                    type="text"
                                    :name="`questions[${index}][question_text]`"
                                    x-model="row.question_text"
                                    required
                                    placeholder="Tuliskan pertanyaan kuis di sini..."
                                    class="w-full px-3.5 py-2.5 rounded-xl bg-[#0f172a] border border-slate-700 focus:border-emerald-400 focus:outline-none text-sm text-white"
                                >
                            </div>
                        </div>

                        <!-- 4 Pilihan Opsi A, B, C, D -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-sky-300 mb-1">Opsi A</label>
                                <input
                                    type="text"
                                    :name="`questions[${index}][opt_a]`"
                                    x-model="row.opt_a"
                                    required
                                    placeholder="Jawaban A"
                                    class="w-full px-3 py-2 rounded-xl bg-[#0f172a] border border-slate-700 text-xs text-white"
                                >
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-sky-300 mb-1">Opsi B</label>
                                <input
                                    type="text"
                                    :name="`questions[${index}][opt_b]`"
                                    x-model="row.opt_b"
                                    required
                                    placeholder="Jawaban B"
                                    class="w-full px-3 py-2 rounded-xl bg-[#0f172a] border border-slate-700 text-xs text-white"
                                >
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-sky-300 mb-1">Opsi C</label>
                                <input
                                    type="text"
                                    :name="`questions[${index}][opt_c]`"
                                    x-model="row.opt_c"
                                    required
                                    placeholder="Jawaban C"
                                    class="w-full px-3 py-2 rounded-xl bg-[#0f172a] border border-slate-700 text-xs text-white"
                                >
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-sky-300 mb-1">Opsi D</label>
                                <input
                                    type="text"
                                    :name="`questions[${index}][opt_d]`"
                                    x-model="row.opt_d"
                                    required
                                    placeholder="Jawaban D"
                                    class="w-full px-3 py-2 rounded-xl bg-[#0f172a] border border-slate-700 text-xs text-white"
                                >
                            </div>
                        </div>

                        <!-- Kunci Jawaban, Poin Benar, Poin Salah, Durasi Detik -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-1">
                            <div>
                                <label class="block text-xs font-bold text-amber-300 mb-1">Kunci Jawaban Benar</label>
                                <select
                                    :name="`questions[${index}][correct_opt]`"
                                    x-model="row.correct_opt"
                                    required
                                    class="w-full px-3 py-2 rounded-xl bg-[#0f172a] border border-amber-400/60 text-xs text-amber-300 font-black"
                                >
                                    <option value="A">Opsi A</option>
                                    <option value="B">Opsi B</option>
                                    <option value="C">Opsi C</option>
                                    <option value="D">Opsi D</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-emerald-300 mb-1">Poin Benar (+)</label>
                                <input
                                    type="number"
                                    :name="`questions[${index}][score_gain]`"
                                    x-model="row.score_gain"
                                    min="1"
                                    max="100"
                                    required
                                    class="w-full px-3 py-2 rounded-xl bg-[#0f172a] border border-slate-700 text-xs text-white font-mono"
                                >
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-rose-300 mb-1">Penalti Salah (-)</label>
                                <input
                                    type="number"
                                    :name="`questions[${index}][score_penalty]`"
                                    x-model="row.score_penalty"
                                    min="0"
                                    max="100"
                                    required
                                    class="w-full px-3 py-2 rounded-xl bg-[#0f172a] border border-slate-700 text-xs text-white font-mono"
                                >
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-300 mb-1">Durasi Soal (Detik)</label>
                                <input
                                    type="number"
                                    :name="`questions[${index}][duration_sec]`"
                                    x-model="row.duration_sec"
                                    min="5"
                                    max="600"
                                    required
                                    class="w-full px-3 py-2 rounded-xl bg-[#0f172a] border border-slate-700 text-xs text-white font-mono"
                                >
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-4 pt-2">
                <button
                    type="button"
                    @click="addRow()"
                    class="px-5 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-600 text-slate-200 font-bold text-xs uppercase tracking-wider transition cursor-pointer"
                >
                    + Tambah Baris Soal Lagi
                </button>

                <button
                    type="submit"
                    class="px-8 py-3.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-400 hover:brightness-110 text-slate-950 font-black text-xs uppercase tracking-wider shadow-[0_0_25px_rgba(16,185,129,0.4)] transition cursor-pointer"
                >
                    💾 Simpan Soal ke Database (<span x-text="rows.length"></span> Soal)
                </button>
            </div>
        </form>
    </section>

    <!-- SECTION 4: Tabel Daftar Soal (Level 1 sampai 15) -->
    <section class="bg-[#0f172a]/90 border border-sky-400/30 rounded-3xl p-6">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
            <div>
                <h2 class="text-base font-black text-white uppercase tracking-wider">
                    4. Daftar Bank Soal Tersimpan: {{ $selectedExam?->title ?? '-' }}
                </h2>
                <p class="text-xs text-slate-400">Menampilkan {{ $questions->count() }} soal di database terurut berdasarkan Level/Tier (1-15).</p>
            </div>

            <div class="flex flex-wrap gap-2">
                @foreach ($exams as $examTab)
                    <a
                        href="{{ route('teacher.exams', ['exam_id' => $examTab->id]) }}"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold border transition {{ $selectedExam && $selectedExam->id === $examTab->id ? 'bg-sky-500 text-slate-950 border-sky-400' : 'bg-slate-900 text-slate-300 border-slate-700' }}"
                    >
                        {{ $examTab->title }} ({{ $examTab->questions_count }})
                    </a>
                @endforeach
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-800 text-sky-300 uppercase">
                        <th class="py-3 px-3">Level</th>
                        <th class="py-3 px-3">Pertanyaan</th>
                        <th class="py-3 px-3">Opsi A / B / C / D</th>
                        <th class="py-3 px-3">Kunci</th>
                        <th class="py-3 px-3">Poin (+/-)</th>
                        <th class="py-3 px-3">Durasi</th>
                        <th class="py-3 px-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/70">
                    @forelse ($questions as $q)
                        <tr class="hover:bg-slate-900/60">
                            <td class="py-3 px-3 font-black whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-lg {{ $q->isSafePoint() ? 'bg-amber-500/20 border border-amber-400 text-amber-300' : 'bg-slate-800 text-sky-300' }}">
                                    Level {{ $q->level_tier }} {{ $q->isSafePoint() ? '[AMAN]' : '' }}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-slate-100 font-medium max-w-md">{{ $q->question_text }}</td>
                            <td class="py-3 px-3 text-slate-400">
                                <div><strong class="text-sky-300">A:</strong> {{ $q->opt_a }} | <strong class="text-sky-300">B:</strong> {{ $q->opt_b }}</div>
                                <div><strong class="text-sky-300">C:</strong> {{ $q->opt_c }} | <strong class="text-sky-300">D:</strong> {{ $q->opt_d }}</div>
                            </td>
                            <td class="py-3 px-3">
                                <span class="px-2.5 py-1 rounded bg-emerald-500/20 border border-emerald-400/40 text-emerald-300 font-black">
                                    {{ $q->correct_opt }}
                                </span>
                            </td>
                            <td class="py-3 px-3 font-mono">
                                <span class="text-emerald-400">+{{ $q->score_gain }}</span> /
                                <span class="text-rose-400">-{{ $q->score_penalty }}</span>
                            </td>
                            <td class="py-3 px-3 font-mono text-slate-300">{{ $q->duration_sec }}s</td>
                            <td class="py-3 px-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <button
                                        type="button"
                                        @click="openEditModal({{ Js::from([
                                            'id' => $q->id,
                                            'update_url' => route('teacher.questions.update', $q),
                                            'level_tier' => $q->level_tier,
                                            'question_text' => $q->question_text,
                                            'opt_a' => $q->opt_a,
                                            'opt_b' => $q->opt_b,
                                            'opt_c' => $q->opt_c,
                                            'opt_d' => $q->opt_d,
                                            'correct_opt' => $q->correct_opt,
                                            'score_gain' => $q->score_gain,
                                            'score_penalty' => $q->score_penalty,
                                            'duration_sec' => $q->duration_sec,
                                        ]) }})"
                                        class="px-2.5 py-1 rounded-lg bg-sky-500/20 hover:bg-sky-500/30 border border-sky-400/40 text-sky-300 font-bold cursor-pointer"
                                    >
                                        Edit
                                    </button>

                                    @if ($canDelete)
                                        <form action="{{ route('teacher.questions.destroy', $q) }}" method="POST" onsubmit="return confirm('Hapus soal ini dari database?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2.5 py-1 rounded-lg bg-rose-500/20 hover:bg-rose-500/30 border border-rose-400/40 text-rose-300 font-bold cursor-pointer">
                                                Hapus
                                            </button>
                                        </form>
                                    @else
                                        <span class="px-2 py-1 rounded-lg bg-slate-900 border border-slate-800 text-slate-500 font-semibold text-[10px]" title="Level Admin tidak dapat menghapus data">
                                            🔒 No Delete
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-6 text-center text-slate-500">Belum ada soal untuk paket ujian ini. Silakan input melalui form di atas atau impor Excel.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <!-- SECTION 5: TABEL FORM REKAPITULASI PESERTA & SKOR / NILAI AKHIR UJIAN SISWA -->
    <section id="section-rekap-peserta" class="bg-[#0f172a]/90 border-2 border-purple-400/40 rounded-3xl p-6 space-y-6 shadow-[0_0_30px_rgba(168,85,247,0.1)]">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-800 pb-4">
            <div>
                <span class="text-xs font-black uppercase tracking-widest text-purple-400">MONITORING &amp; HASIL UJIAN PESERTA</span>
                <h2 class="text-lg font-black text-white mt-0.5">
                    5. Tabel Daftar Siswa Peserta Ujian &amp; Skor / Nilai Akhir
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">
                    Menampilkan data lengkap siswa yang telah maupun sedang mengikuti ujian mulai dari NIS, Nama, Kelas, Sesi Ujian, Benar/Salah, hingga Skor Akhir.
                </p>
            </div>

            <!-- Statistik Ringkas Peserta -->
            <div class="flex flex-wrap gap-2.5 text-xs">
                <div class="px-3.5 py-2 rounded-xl bg-slate-900 border border-slate-700">
                    <span class="text-slate-400">Total Sesi Peserta:</span>
                    <strong class="text-white font-black ml-1">{{ $participantSessions->count() }}</strong>
                </div>
                <div class="px-3.5 py-2 rounded-xl bg-emerald-950/60 border border-emerald-500/40">
                    <span class="text-emerald-300">Selesai:</span>
                    <strong class="text-emerald-200 font-black ml-1">{{ $participantSessions->where('status', 'finished')->count() }}</strong>
                </div>
                <div class="px-3.5 py-2 rounded-xl bg-sky-950/60 border border-sky-500/40">
                    <span class="text-sky-300">Sedang Ujian:</span>
                    <strong class="text-sky-200 font-black ml-1">{{ $participantSessions->where('status', 'ongoing')->count() }}</strong>
                </div>
                <div class="px-3.5 py-2 rounded-xl bg-rose-950/60 border border-rose-500/40">
                    <span class="text-rose-300">Gugur (Eliminated):</span>
                    <strong class="text-rose-200 font-black ml-1">{{ $participantSessions->where('status', 'eliminated')->count() }}</strong>
                </div>
            </div>
        </div>

        <!-- Form Filter & Pencarian Peserta Ujian -->
        <form action="{{ route('teacher.exams') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 bg-[#070b19] p-4 rounded-2xl border border-slate-800">
            @if ($selectedExam)
                <input type="hidden" name="exam_id" value="{{ $selectedExam->id }}">
            @endif

            <div class="lg:col-span-3">
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Cari NIS / Nama Siswa</label>
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Ketik NIS atau nama siswa..."
                    class="w-full px-3.5 py-2 rounded-xl bg-[#0f172a] border border-slate-700 text-xs text-white"
                >
            </div>

            <div class="lg:col-span-3">
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Filter Paket / Sesi Ujian</label>
                <select name="session_exam_id" class="w-full px-3.5 py-2 rounded-xl bg-[#0f172a] border border-slate-700 text-xs text-white font-semibold">
                    <option value="all" @selected(request('session_exam_id', 'all') === 'all')>Semua Paket Ujian</option>
                    @foreach ($exams as $examFilter)
                        <option value="{{ $examFilter->id }}" @selected((int) request('session_exam_id') === $examFilter->id)>
                            {{ $examFilter->title }} ({{ $examFilter->subject }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-2">
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Filter Kelas</label>
                <select name="class_filter" class="w-full px-3.5 py-2 rounded-xl bg-[#0f172a] border border-slate-700 text-xs text-white font-semibold">
                    <option value="">Semua Kelas</option>
                    @foreach ($availableClasses as $classItem)
                        <option value="{{ $classItem }}" @selected(request('class_filter') === $classItem)>{{ $classItem }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-2">
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Status Ujian</label>
                <select name="status_filter" class="w-full px-3.5 py-2 rounded-xl bg-[#0f172a] border border-slate-700 text-xs text-white font-semibold">
                    <option value="">Semua Status</option>
                    <option value="finished" @selected(request('status_filter') === 'finished')>Selesai (Finished)</option>
                    <option value="ongoing" @selected(request('status_filter') === 'ongoing')>Sedang Berlangsung (Ongoing)</option>
                    <option value="eliminated" @selected(request('status_filter') === 'eliminated')>Tereliminasi (Game Over)</option>
                </select>
            </div>

            <div class="lg:col-span-2 flex items-end gap-2">
                <button type="submit" class="flex-1 py-2 px-3 rounded-xl bg-purple-500 hover:bg-purple-400 text-slate-950 font-black text-xs uppercase tracking-wider transition cursor-pointer">
                    Filter
                </button>
                <a
                    href="{{ route('teacher.exams', $selectedExam ? ['exam_id' => $selectedExam->id] : []) }}#section-rekap-peserta"
                    class="py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs transition"
                >
                    Reset
                </a>
            </div>
        </form>

        <!-- Tabel Lengkap Peserta Ujian (Dari NIS sampai Skor / Nilai Akhir) -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-800 text-purple-300 uppercase bg-slate-900/60">
                        <th class="py-3.5 px-3">No</th>
                        <th class="py-3.5 px-3">NIS</th>
                        <th class="py-3.5 px-3">Nama Lengkap Siswa</th>
                        <th class="py-3.5 px-3">Kelas</th>
                        <th class="py-3.5 px-3">Sesi / Paket Ujian</th>
                        <th class="py-3.5 px-3">Level Terakhir</th>
                        <th class="py-3.5 px-3">Benar / Salah</th>
                        <th class="py-3.5 px-3">Modal &amp; Safe Point</th>
                        <th class="py-3.5 px-3">Status Ujian</th>
                        <th class="py-3.5 px-3">Waktu Ujian</th>
                        <th class="py-3.5 px-3 text-right">Skor / Nilai Akhir</th>
                        <th class="py-3.5 px-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/70">
                    @forelse ($participantSessions as $idx => $participant)
                        <tr class="hover:bg-slate-900/70 transition">
                            <td class="py-3.5 px-3 font-mono text-slate-400">{{ $idx + 1 }}</td>
                            <td class="py-3.5 px-3 font-mono font-bold text-sky-300 whitespace-nowrap">
                                {{ $participant->student?->nis ?? '-' }}
                            </td>
                            <td class="py-3.5 px-3 font-bold text-white">
                                {{ $participant->student?->full_name ?? '-' }}
                            </td>
                            <td class="py-3.5 px-3 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-lg bg-slate-800 border border-slate-700 text-slate-200 font-semibold">
                                    {{ $participant->student?->class_name ?? '-' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-3">
                                <div class="font-bold text-slate-100">{{ $participant->exam?->title ?? '-' }}</div>
                                <div class="text-[11px] text-slate-400">
                                    {{ $participant->exam?->subject ?? '-' }} •
                                    {{ $participant->exam?->timer_mode === 'mode_a' ? 'Mode A (Per Soal)' : 'Mode B (Global)' }}
                                </div>
                            </td>
                            <td class="py-3.5 px-3 font-mono whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded bg-sky-500/15 border border-sky-400/30 text-sky-300 font-bold">
                                    Level {{ $participant->current_step }} / {{ count($participant->question_order ?? []) ?: 15 }}
                                </span>
                            </td>
                            <td class="py-3.5 px-3 font-mono whitespace-nowrap">
                                <span class="text-emerald-400 font-bold" title="Jawaban Benar">✓ {{ $participant->correct_answers_count ?? 0 }}</span>
                                <span class="text-slate-600 mx-1">/</span>
                                <span class="text-rose-400 font-bold" title="Jawaban Salah">✕ {{ $participant->wrong_answers_count ?? 0 }}</span>
                                <span class="text-slate-400 text-[11px] ml-1">({{ $participant->total_answered_count ?? 0 }} soal)</span>
                            </td>
                            <td class="py-3.5 px-3 font-mono whitespace-nowrap text-slate-300">
                                <div>Modal: <strong class="text-sky-300">{{ $participant->exam?->starting_score ?? 20 }}</strong></div>
                                <div class="text-[11px] text-amber-300">Safe: {{ $participant->safe_score }} Poin</div>
                            </td>
                            <td class="py-3.5 px-3 whitespace-nowrap">
                                @if ($participant->status === 'finished')
                                    <span class="px-2.5 py-1 rounded-lg bg-emerald-500/20 border border-emerald-400/50 text-emerald-300 font-black uppercase text-[10px] tracking-wider">
                                        ✓ Selesai
                                    </span>
                                @elseif ($participant->status === 'eliminated')
                                    <span class="px-2.5 py-1 rounded-lg bg-rose-500/20 border border-rose-400/50 text-rose-300 font-black uppercase text-[10px] tracking-wider">
                                        ✕ Gugur (0 Poin)
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-lg bg-amber-500/20 border border-amber-400/50 text-amber-300 font-black uppercase text-[10px] tracking-wider">
                                        ⏳ Sedang Ujian
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-3 text-[11px] text-slate-400 whitespace-nowrap">
                                <div>Mulai: {{ $participant->started_at?->format('d/m/Y H:i') ?? '-' }}</div>
                                @if ($participant->finished_at)
                                    <div class="text-emerald-400">Selesai: {{ $participant->finished_at->format('d/m/Y H:i') }}</div>
                                @endif
                            </td>
                            <td class="py-3.5 px-3 text-right whitespace-nowrap">
                                <span class="px-3 py-1.5 rounded-xl font-mono text-sm font-black {{ $participant->status === 'eliminated' ? 'bg-rose-950/90 border border-rose-500/50 text-rose-300' : 'bg-amber-500/20 border border-amber-400/60 text-amber-300' }}">
                                    {{ $participant->current_score }} Poin
                                </span>
                            </td>
                            <td class="py-3.5 px-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <a
                                        href="{{ route('exam.arena', $participant->session_token) }}"
                                        class="px-2.5 py-1 rounded-lg bg-sky-500/20 hover:bg-sky-500/30 border border-sky-400/40 text-sky-300 font-bold"
                                    >
                                        Detail
                                    </a>
                                    @if ($canDelete)
                                        <form action="{{ route('teacher.sessions.destroy', $participant) }}" method="POST" onsubmit="return confirm('Hapus riwayat sesi peserta ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2.5 py-1 rounded-lg bg-rose-500/20 hover:bg-rose-500/30 border border-rose-400/40 text-rose-300 font-bold cursor-pointer">
                                                Hapus
                                            </button>
                                        </form>
                                    @else
                                        <span class="px-2 py-1 rounded-lg bg-slate-900 border border-slate-800 text-slate-500 font-semibold text-[10px]" title="Level Admin tidak dapat menghapus data">
                                            🔒 No Delete
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="py-8 text-center text-slate-500">
                                Belum ada peserta siswa yang mengikuti ujian sesuai filter yang dipilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <!-- SECTION 6: BACKOFFICE MANAJEMEN USER & GURU (SUPERVISOR & ADMIN) -->
    <section id="section-backoffice-users" class="bg-[#0f172a]/90 border-2 border-cyan-400/40 rounded-3xl p-6 space-y-6 shadow-[0_0_30px_rgba(34,211,238,0.1)]">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-800 pb-4">
            <div>
                <span class="text-xs font-black uppercase tracking-widest text-cyan-400">BACKOFFICE PENGATURAN HAK AKSES GURU &amp; USER</span>
                <h2 class="text-lg font-black text-white mt-0.5">
                    6. Backoffice Manajemen User / Guru (Login Panel Ujian)
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">
                    Kelola akun guru dan pengelola yang diizinkan masuk ke halaman <code>/teacher/exams</code> dengan pilihan tingkat <strong>Supervisor (Full Akses)</strong> atau <strong>Admin (Full Akses Tanpa Delete Data)</strong>.
                </p>
            </div>

            <div class="flex flex-wrap gap-2 text-xs">
                <div class="px-3.5 py-2 rounded-xl bg-amber-950/60 border border-amber-400/40">
                    <span class="text-amber-300 font-bold">👑 Supervisor:</span>
                    <strong class="text-white font-black ml-1">{{ ($users ?? collect())->where('role', 'supervisor')->count() }} User</strong>
                </div>
                <div class="px-3.5 py-2 rounded-xl bg-sky-950/60 border border-sky-400/40">
                    <span class="text-sky-300 font-bold">🛡️ Admin / Guru:</span>
                    <strong class="text-white font-black ml-1">{{ ($users ?? collect())->where('role', 'admin')->count() }} User</strong>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Form Tambah User / Guru Baru (5 Kolom) -->
            <div class="lg:col-span-5 bg-[#070b19] border border-cyan-500/30 rounded-2xl p-5 space-y-4">
                <div class="border-b border-slate-800 pb-3">
                    <h3 class="text-sm font-black text-cyan-300 uppercase tracking-wider">
                        + Daftarkan User / Guru Baru
                    </h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">
                        User yang didaftarkan dapat langsung login di halaman <code>/login</code>.
                    </p>
                </div>

                <form action="{{ route('teacher.users.store') }}" method="POST" class="space-y-3.5">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Nama Lengkap Guru / User</label>
                        <input
                            type="text"
                            name="name"
                            required
                            placeholder="Contoh: Drs. Hendra Wijaya, M.Pd."
                            class="w-full px-3.5 py-2.5 rounded-xl bg-[#0f172a] border border-slate-700 focus:border-cyan-400 focus:outline-none text-xs text-white"
                        >
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-cyan-300 mb-1">Username Login</label>
                            <input
                                type="text"
                                name="username"
                                required
                                placeholder="Contoh: guru_matematika"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-[#0f172a] border border-cyan-500/40 focus:border-cyan-400 focus:outline-none text-xs text-white font-mono"
                            >
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-amber-300 mb-1">Password Login</label>
                            <input
                                type="text"
                                name="password"
                                required
                                minlength="4"
                                placeholder="Minimal 4 karakter"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-[#0f172a] border border-amber-500/40 focus:border-amber-400 focus:outline-none text-xs text-white font-mono"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Tingkat Akses (Role Backoffice)</label>
                        <select
                            name="role"
                            required
                            class="w-full px-3.5 py-2.5 rounded-xl bg-[#0f172a] border border-sky-500/50 text-xs text-white font-bold"
                        >
                            <option value="admin">🛡️ ADMIN / GURU — Full Akses (Tidak Bisa Delete Data)</option>
                            @if ($canDelete)
                                <option value="supervisor">👑 SUPERVISOR — Full Akses (Bisa Buat, Edit &amp; Delete Semua Data)</option>
                            @endif
                        </select>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-900/90 border border-slate-800 text-[11px] text-slate-300 space-y-1">
                        <div><strong class="text-amber-300">👑 Supervisor:</strong> Memiliki akses penuh termasuk menghapus paket ujian, soal, riwayat nilai siswa, dan user.</div>
                        <div><strong class="text-sky-300">🛡️ Admin:</strong> Memiliki akses penuh membuat/mengedit paket ujian &amp; soal serta melihat nilai siswa, namun <strong>tidak bisa menghapus data</strong>.</div>
                    </div>

                    <button
                        type="submit"
                        class="w-full py-3 rounded-xl bg-gradient-to-r from-cyan-500 to-sky-400 hover:brightness-110 text-slate-950 font-black text-xs uppercase tracking-wider transition cursor-pointer"
                    >
                        + Simpan &amp; Daftarkan User Baru
                    </button>
                </form>
            </div>

            <!-- Tabel Daftar User / Guru Terdaftar (7 Kolom) -->
            <div class="lg:col-span-7 bg-[#070b19] border border-slate-800 rounded-2xl p-5 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3 mb-4">
                        <h3 class="text-sm font-black text-white uppercase tracking-wider">
                            Daftar Akun User &amp; Guru Terdaftar ({{ ($users ?? collect())->count() }})
                        </h3>
                        <span class="text-[11px] text-slate-400">Akses Login <code>/teacher/exams</code></span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="border-b border-slate-800 text-cyan-300 uppercase">
                                    <th class="py-3 px-3">No</th>
                                    <th class="py-3 px-3">Nama Guru / User</th>
                                    <th class="py-3 px-3">Username</th>
                                    <th class="py-3 px-3">Tingkat Akses</th>
                                    <th class="py-3 px-3">Hak Akses Data</th>
                                    <th class="py-3 px-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/70">
                                @forelse ($users ?? [] as $uIdx => $userItem)
                                    <tr class="hover:bg-slate-900/60 transition">
                                        <td class="py-3 px-3 font-mono text-slate-400">{{ $uIdx + 1 }}</td>
                                        <td class="py-3 px-3 font-bold text-white">
                                            {{ $userItem->name }}
                                            @if ($currentUser && $currentUser->id === $userItem->id)
                                                <span class="ml-1 px-1.5 py-0.5 rounded bg-emerald-500/20 border border-emerald-400/40 text-emerald-300 text-[10px] font-black">
                                                    ANDA
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-3 font-mono font-bold text-cyan-300">
                                            {{ $userItem->username }}
                                        </td>
                                        <td class="py-3 px-3 whitespace-nowrap">
                                            @if ($userItem->isSupervisor())
                                                <span class="px-2.5 py-1 rounded-lg bg-amber-500/20 border border-amber-400/60 text-amber-300 font-black text-[10px] uppercase tracking-wider">
                                                    👑 SUPERVISOR
                                                </span>
                                            @else
                                                <span class="px-2.5 py-1 rounded-lg bg-sky-500/20 border border-sky-400/60 text-sky-300 font-black text-[10px] uppercase tracking-wider">
                                                    🛡️ ADMIN
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-3 text-[11px]">
                                            @if ($userItem->isSupervisor())
                                                <span class="text-emerald-300 font-semibold">✓ Full Akses (+ Delete)</span>
                                            @else
                                                <span class="text-slate-300">✓ Full Akses <span class="text-rose-400 font-semibold">(Tanpa Delete)</span></span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-3 text-right whitespace-nowrap">
                                            <div class="inline-flex items-center gap-1.5">
                                                @if ($canDelete || ! $userItem->isSupervisor())
                                                    <button
                                                        type="button"
                                                        @click="openEditUserModal({{ Js::from([
                                                            'id' => $userItem->id,
                                                            'update_url' => route('teacher.users.update', $userItem),
                                                            'name' => $userItem->name,
                                                            'username' => $userItem->username,
                                                            'role' => $userItem->role,
                                                        ]) }})"
                                                        class="px-2.5 py-1 rounded-lg bg-sky-500/20 hover:bg-sky-500/30 border border-sky-400/40 text-sky-300 font-bold cursor-pointer"
                                                    >
                                                        Edit
                                                    </button>
                                                @endif

                                                @if ($canDelete)
                                                    @if ($currentUser && $currentUser->id !== $userItem->id)
                                                        <form action="{{ route('teacher.users.destroy', $userItem) }}" method="POST" onsubmit="return confirm('Hapus akun user/guru ini dari Backoffice?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="px-2.5 py-1 rounded-lg bg-rose-500/20 hover:bg-rose-500/30 border border-rose-400/40 text-rose-300 font-bold cursor-pointer">
                                                                Hapus
                                                            </button>
                                                        </form>
                                                    @endif
                                                @else
                                                    <span class="px-2 py-1 rounded-lg bg-slate-900 border border-slate-800 text-slate-500 font-semibold text-[10px]">
                                                        🔒 No Delete
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-6 text-center text-slate-500">Belum ada user terdaftar.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal Edit Soal -->
    <div
        x-show="editingQuestion !== null"
        style="display: none;"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4"
    >
        <div
            @click.outside="closeEditModal()"
            class="w-full max-w-2xl bg-[#0f172a] border-2 border-sky-400/60 rounded-3xl p-6 space-y-5 shadow-[0_0_50px_rgba(56,189,248,0.25)]"
        >
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-base font-black text-sky-300 uppercase tracking-wider">
                    ✏️ Edit Soal #<span x-text="editingQuestion?.id"></span>
                </h3>
                <button type="button" @click="closeEditModal()" class="text-slate-400 hover:text-white text-sm font-bold cursor-pointer">
                    ✕ Tutup
                </button>
            </div>

            <template x-if="editingQuestion">
                <form :action="editingQuestion.update_url" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                        <div class="sm:col-span-3">
                            <label class="block text-xs font-bold text-sky-300 mb-1">Level / Tier (1-15)</label>
                            <select name="level_tier" x-model="editingQuestion.level_tier" required class="w-full px-3 py-2.5 rounded-xl bg-[#070b19] border border-slate-700 text-xs text-white font-bold">
                                @for ($lvl = 1; $lvl <= 15; $lvl++)
                                    <option value="{{ $lvl }}">Level {{ $lvl }} {{ in_array($lvl, [5, 10]) ? '[AMAN]' : '' }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="sm:col-span-9">
                            <label class="block text-xs font-bold text-slate-200 mb-1">Pertanyaan</label>
                            <input type="text" name="question_text" x-model="editingQuestion.question_text" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#070b19] border border-slate-700 text-xs text-white">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-sky-300 mb-1">Opsi A</label>
                            <input type="text" name="opt_a" x-model="editingQuestion.opt_a" required class="w-full px-3 py-2 rounded-xl bg-[#070b19] border border-slate-700 text-xs text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-sky-300 mb-1">Opsi B</label>
                            <input type="text" name="opt_b" x-model="editingQuestion.opt_b" required class="w-full px-3 py-2 rounded-xl bg-[#070b19] border border-slate-700 text-xs text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-sky-300 mb-1">Opsi C</label>
                            <input type="text" name="opt_c" x-model="editingQuestion.opt_c" required class="w-full px-3 py-2 rounded-xl bg-[#070b19] border border-slate-700 text-xs text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-sky-300 mb-1">Opsi D</label>
                            <input type="text" name="opt_d" x-model="editingQuestion.opt_d" required class="w-full px-3 py-2 rounded-xl bg-[#070b19] border border-slate-700 text-xs text-white">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-amber-300 mb-1">Kunci Jawaban</label>
                            <select name="correct_opt" x-model="editingQuestion.correct_opt" required class="w-full px-3 py-2 rounded-xl bg-[#070b19] border border-amber-400/50 text-xs text-amber-300 font-black">
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-emerald-300 mb-1">Poin Benar (+)</label>
                            <input type="number" name="score_gain" x-model="editingQuestion.score_gain" min="1" max="100" required class="w-full px-3 py-2 rounded-xl bg-[#070b19] border border-slate-700 text-xs text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-rose-300 mb-1">Poin Salah (-)</label>
                            <input type="number" name="score_penalty" x-model="editingQuestion.score_penalty" min="0" max="100" required class="w-full px-3 py-2 rounded-xl bg-[#070b19] border border-slate-700 text-xs text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Durasi (Detik)</label>
                            <input type="number" name="duration_sec" x-model="editingQuestion.duration_sec" min="5" max="600" required class="w-full px-3 py-2 rounded-xl bg-[#070b19] border border-slate-700 text-xs text-white">
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-3">
                        <button type="button" @click="closeEditModal()" class="px-4 py-2.5 rounded-xl bg-slate-800 text-slate-300 text-xs font-bold cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-sky-500 hover:bg-sky-400 text-slate-950 text-xs font-black uppercase tracking-wider cursor-pointer">
                            Simpan Perubahan Soal
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </div>

    <!-- Modal Edit User / Guru Backoffice -->
    <div
        x-show="editingUser !== null"
        style="display: none;"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4"
    >
        <div
            @click.outside="closeEditUserModal()"
            class="w-full max-w-lg bg-[#0f172a] border-2 border-cyan-400/60 rounded-3xl p-6 space-y-5 shadow-[0_0_50px_rgba(34,211,238,0.25)]"
        >
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-base font-black text-cyan-300 uppercase tracking-wider">
                    👤 Edit Akun User / Guru #<span x-text="editingUser?.id"></span>
                </h3>
                <button type="button" @click="closeEditUserModal()" class="text-slate-400 hover:text-white text-sm font-bold cursor-pointer">
                    ✕ Tutup
                </button>
            </div>

            <template x-if="editingUser">
                <form :action="editingUser.update_url" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-bold text-slate-200 mb-1">Nama Lengkap Guru / User</label>
                        <input type="text" name="name" x-model="editingUser.name" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#070b19] border border-slate-700 text-xs text-white">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-cyan-300 mb-1">Username Login</label>
                        <input type="text" name="username" x-model="editingUser.username" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#070b19] border border-cyan-500/40 text-xs text-white font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-amber-300 mb-1">Password Baru (Kosongkan jika tidak ingin mengubah)</label>
                        <input type="text" name="password" x-model="editingUser.password" placeholder="Isi hanya jika ingin mengganti password..." class="w-full px-3.5 py-2.5 rounded-xl bg-[#070b19] border border-slate-700 text-xs text-white font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-200 mb-1">Tingkat Akses (Role)</label>
                        <select name="role" x-model="editingUser.role" required class="w-full px-3.5 py-2.5 rounded-xl bg-[#070b19] border border-sky-500/50 text-xs text-white font-bold">
                            <option value="admin">🛡️ ADMIN — Full Akses (Tidak Bisa Delete Data)</option>
                            @if ($canDelete)
                                <option value="supervisor">👑 SUPERVISOR — Full Akses (+ Bisa Delete Data)</option>
                            @endif
                        </select>
                    </div>

                    <div class="flex justify-end gap-3 pt-3">
                        <button type="button" @click="closeEditUserModal()" class="px-4 py-2.5 rounded-xl bg-slate-800 text-slate-300 text-xs font-bold cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 text-xs font-black uppercase tracking-wider cursor-pointer">
                            Simpan Perubahan User
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </div>
</div>

</body>
</html>
