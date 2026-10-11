<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Ujian Online') }} — Arena Millionaire CBT</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    @endif

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }

        /* Deep Navy Millionaire Stage Background */
        .millionaire-stage {
            background-color: #070b19;
            background-image:
                radial-gradient(circle at 50% 18%, rgba(56, 189, 248, 0.16) 0%, rgba(15, 23, 42, 0.85) 45%, #070b19 85%),
                radial-gradient(circle at 15% 85%, rgba(30, 64, 175, 0.18) 0%, transparent 50%),
                radial-gradient(circle at 85% 85%, rgba(245, 158, 11, 0.08) 0%, transparent 50%);
        }

        /* Hexagonal Wrapper & Inner Clip Path (Mobile & Desktop) */
        .hex-outer {
            clip-path: polygon(18px 0%, calc(100% - 18px) 0%, 100% 50%, calc(100% - 18px) 100%, 18px 100%, 0% 50%);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .hex-inner {
            clip-path: polygon(17px 0%, calc(100% - 17px) 0%, calc(100% - 2px) 50%, calc(100% - 17px) 100%, 17px 100%, 2px 50%);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Hexagonal Action Buttons for Mobile Sticky Bottom Bar */
        .hex-action-outer {
            clip-path: polygon(14px 0%, calc(100% - 14px) 0%, 100% 50%, calc(100% - 14px) 100%, 14px 100%, 0% 50%);
            transition: all 0.2s ease;
        }

        .hex-action-inner {
            clip-path: polygon(13px 0%, calc(100% - 13px) 0%, calc(100% - 2px) 50%, calc(100% - 13px) 100%, 13px 100%, 2px 50%);
            transition: all 0.2s ease;
        }

        /* Question Box Hexagon (Taller multi-line support) */
        .hex-question-outer {
            clip-path: polygon(22px 0%, calc(100% - 22px) 0%, 100% 50%, calc(100% - 22px) 100%, 22px 100%, 0% 50%);
            filter: drop-shadow(0 0 18px rgba(56, 189, 248, 0.45));
        }

        .hex-question-inner {
            clip-path: polygon(21px 0%, calc(100% - 21px) 0%, calc(100% - 2px) 50%, calc(100% - 21px) 100%, 21px 100%, 2px 50%);
        }

        @media (min-width: 640px) {
            .hex-outer {
                clip-path: polygon(26px 0%, calc(100% - 26px) 0%, 100% 50%, calc(100% - 26px) 100%, 26px 100%, 0% 50%);
            }
            .hex-inner {
                clip-path: polygon(25px 0%, calc(100% - 25px) 0%, calc(100% - 2px) 50%, calc(100% - 25px) 100%, 25px 100%, 2px 50%);
            }
            .hex-question-outer {
                clip-path: polygon(34px 0%, calc(100% - 34px) 0%, 100% 50%, calc(100% - 34px) 100%, 34px 100%, 0% 50%);
            }
            .hex-question-inner {
                clip-path: polygon(33px 0%, calc(100% - 33px) 0%, calc(100% - 2px) 50%, calc(100% - 33px) 100%, 33px 100%, 2px 50%);
            }
        }

        /* Neon Glows */
        .glow-cyan {
            filter: drop-shadow(0 0 10px rgba(56, 189, 248, 0.45));
        }

        .glow-amber {
            filter: drop-shadow(0 0 14px rgba(245, 158, 11, 0.75));
        }

        .glow-emerald {
            filter: drop-shadow(0 0 14px rgba(16, 185, 129, 0.75));
        }

        .glow-rose {
            filter: drop-shadow(0 0 14px rgba(244, 63, 94, 0.75));
        }

        /* Stage Horizontal Connector Line */
        .stage-line {
            height: 2px;
            background: linear-gradient(90deg, rgba(56, 189, 248, 0.12) 0%, rgba(56, 189, 248, 0.9) 50%, rgba(56, 189, 248, 0.12) 100%);
            box-shadow: 0 0 10px rgba(56, 189, 248, 0.6);
        }

        .stage-line-amber {
            height: 2px;
            background: linear-gradient(90deg, rgba(245, 158, 11, 0.2) 0%, rgba(245, 158, 11, 0.98) 50%, rgba(245, 158, 11, 0.2) 100%);
            box-shadow: 0 0 12px rgba(245, 158, 11, 0.8);
        }

        /* Hide scrollbar on horizontal mobile level strip */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</head>
<body class="millionaire-stage min-h-screen text-slate-100 antialiased selection:bg-sky-500 selection:text-slate-950">

<div
    x-data="examArena(@js($arenaState), {
        answerUrl: '{{ route('exam.answer', ['token' => $session->session_token]) }}',
        lifelineUrl: '{{ route('exam.lifeline5050', ['token' => $session->session_token]) }}',
        globalTimeoutUrl: '{{ route('exam.timeoutGlobal', ['token' => $session->session_token]) }}',
        loginUrl: '{{ route('exam.login') }}'
    })"
    x-init="initArena()"
    class="min-h-screen flex flex-col"
>
    <!-- TOMBOL SWITCHER TAMPILAN DESKTOP / MOCKUP SMARTPHONE (Tampil di layar Desktop lg ke atas) -->
    <div class="hidden lg:flex items-center justify-between max-w-7xl w-full mx-auto px-8 pt-4">
        <div class="text-xs text-slate-400">
            Mode Tampilan Layar: <strong class="text-sky-300" x-text="mobileMockupMode ? 'Simulasi Smartphone (Mobile UI)' : 'Layar Penuh Desktop'"></strong>
        </div>
        <div class="inline-flex items-center gap-1.5 bg-[#0f172a]/95 border border-sky-400/40 rounded-full p-1 shadow-[0_0_20px_rgba(56,189,248,0.15)]">
            <button
                type="button"
                @click="toggleMobileMockup(false)"
                class="px-3.5 py-1.5 rounded-full text-xs font-black uppercase tracking-wider transition cursor-pointer"
                :class="!mobileMockupMode ? 'bg-sky-500 text-slate-950 shadow' : 'text-slate-400 hover:text-white'"
            >
                🖥️ Tampilan Desktop
            </button>
            <button
                type="button"
                @click="toggleMobileMockup(true)"
                class="px-3.5 py-1.5 rounded-full text-xs font-black uppercase tracking-wider transition cursor-pointer"
                :class="mobileMockupMode ? 'bg-amber-400 text-slate-950 shadow' : 'text-slate-400 hover:text-white'"
            >
                📱 Tampilan Smartphone
            </button>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 1. TAMPILAN SMARTPHONE / MOBILE (SESUAI MOCKUP)                           -->
    <!--    Aktif otomatis di layar Smartphone (< lg) ATAU saat mode Smartphone ON -->
    <!-- ========================================================================= -->
    <div
        x-show="isMobileViewport || mobileMockupMode"
        x-cloak
        :class="mobileMockupMode && !isMobileViewport
            ? 'my-4 mx-auto w-[412px] max-w-full rounded-[46px] border-[10px] border-slate-800/95 bg-[#070b19] shadow-[0_0_90px_rgba(56,189,248,0.28)] overflow-hidden flex flex-col min-h-[844px] max-h-[890px] relative'
            : 'flex-1 flex flex-col justify-between pb-24 max-w-md mx-auto w-full'"
    >
        <!-- Status Bar Ilustrasi Saat Mode Mockup di Desktop -->
        <div
            x-show="mobileMockupMode && !isMobileViewport"
            class="bg-[#050814] py-1.5 px-6 flex items-center justify-between text-[11px] font-bold text-slate-400 border-b border-slate-800/60"
        >
            <span>9:41</span>
            <div class="w-20 h-3.5 bg-slate-900 rounded-full border border-slate-800"></div>
            <span>5G • 100%</span>
        </div>

        <!-- A. TOP COMPACT HUD (Sesuai Mockup: Judul & Peserta, 2 Kartu Poin & Waktu, Timeline Level) -->
        <div class="px-3.5 pt-3 pb-2 space-y-2.5 bg-[#070b19]/95 backdrop-blur-md sticky top-0 z-20">
            <!-- Baris 1: Nama Ujian - Peserta (Kelas) -->
            <div class="text-center py-1 px-2 rounded-xl bg-[#0f172a]/70 border border-sky-500/20">
                <p class="text-xs sm:text-sm font-extrabold text-white tracking-tight truncate">
                    <span x-text="state.exam.title"></span>
                    <span class="text-sky-400 mx-1">-</span>
                    <span class="text-slate-200 font-semibold">
                        Peserta: <strong class="text-white" x-text="state.student.full_name"></strong>
                        (<span x-text="state.student.class_name"></span>)
                    </span>
                </p>
            </div>

            <!-- Baris 2: 2 Kotak Sejajar (POIN SAAT INI & SISA WAKTU) -->
            <div class="grid grid-cols-2 gap-2.5">
                <!-- Kotak Kiri: POIN SAAT INI -->
                <div class="bg-[#0f172a]/95 border border-sky-400/40 rounded-2xl px-3.5 py-3 text-center shadow-[0_0_20px_rgba(56,189,248,0.12)] flex flex-col items-center justify-center">
                    <span class="text-[10px] font-extrabold uppercase tracking-widest text-slate-300">
                        POIN SAAT INI:
                    </span>
                    <div class="my-0.5 flex items-baseline justify-center gap-1.5">
                        <span class="text-2xl sm:text-3xl font-black text-white tracking-tight" x-text="state.session.current_score"></span>
                        <span class="text-base sm:text-lg font-extrabold text-white">Poin</span>
                    </div>
                    <span class="text-[11px] font-medium text-slate-400">
                        (Safe Point: <span class="text-slate-200 font-bold" x-text="state.session.safe_score"></span>)
                    </span>
                </div>

                <!-- Kotak Kanan: SISA WAKTU -->
                <div
                    class="bg-[#0f172a]/95 border rounded-2xl px-3.5 py-3 text-center flex flex-col items-center justify-between transition-colors"
                    :class="timerRemaining <= 10 && state.session.status === 'ongoing'
                        ? 'border-rose-500 shadow-[0_0_20px_rgba(244,63,94,0.4)] animate-pulse'
                        : 'border-sky-400/40 shadow-[0_0_20px_rgba(56,189,248,0.12)]'"
                >
                    <span class="text-[10px] font-extrabold uppercase tracking-widest text-slate-300">
                        SISA WAKTU:
                    </span>
                    <div class="my-0.5">
                        <span
                            class="text-2xl sm:text-3xl font-mono font-black tracking-wider"
                            :class="timerRemaining <= 10 ? 'text-rose-400' : 'text-white'"
                            x-text="formattedTimer()"
                        ></span>
                    </div>
                    <!-- Progress Bar Cyan Glow -->
                    <div class="w-full bg-slate-800/90 h-2 rounded-full overflow-hidden mt-1">
                        <div
                            class="h-full transition-all duration-300 rounded-full"
                            :class="timerRemaining <= 10 ? 'bg-rose-500' : 'bg-gradient-to-r from-sky-400 to-cyan-300 shadow-[0_0_10px_rgba(56,189,248,0.8)]'"
                            :style="`width: ${timerPercentage()}%`"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- Baris 3: Kartu LEVEL 12 / 15 + Tombol Lihat Tangga Level + Stepper Bulat Terhubung -->
            <div class="bg-[#0f172a]/95 border border-sky-400/35 rounded-2xl px-3.5 py-2.5 space-y-2.5 shadow-[0_0_20px_rgba(7,11,25,0.6)]">
                <div class="flex items-center justify-between">
                    <div class="text-xs sm:text-sm font-black tracking-wide text-white uppercase">
                        LEVEL <span x-text="state.question ? state.question.level_tier : state.session.current_step"></span>
                        <span class="text-slate-400 font-semibold">/ <span x-text="state.session.max_level"></span></span>
                    </div>

                    <button
                        type="button"
                        @click="showMobileLadderDrawer = true"
                        class="px-3 py-1 rounded-lg bg-slate-800/90 hover:bg-slate-700 border border-slate-600/80 text-slate-100 text-[11px] font-semibold transition cursor-pointer"
                    >
                        Lihat Tangga Level
                    </button>
                </div>

                <!-- Stepper Timeline Horizontal Terhubung (1 s/d 15) -->
                <div
                    x-ref="mobileTimeline"
                    class="relative flex items-start gap-2 overflow-x-auto no-scrollbar pt-1 pb-0.5"
                >
                    <template x-for="item in (state.ladder || [])" :key="'mob-step-' + item.level">
                        <div
                            :data-level="item.level"
                            @click="showMobileLadderDrawer = true"
                            class="relative flex flex-col items-center shrink-0 min-w-[28px] cursor-pointer group"
                        >
                            <!-- Garis Penghubung Horizontal Antar Level -->
                            <div
                                x-show="item.level > 1"
                                class="absolute top-3.5 -left-3 w-4 h-[2px] -z-0"
                                :class="item.status === 'completed' ? 'bg-emerald-500' : 'bg-slate-700'"
                            ></div>

                            <!-- Lingkaran / Badge Level -->
                            <div
                                class="relative z-10 w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-black transition-all"
                                :class="item.status === 'completed'
                                    ? (item.is_correct === false
                                        ? 'bg-rose-500 text-slate-950 border-2 border-rose-300 shadow-[0_0_8px_rgba(244,63,94,0.5)]'
                                        : 'bg-emerald-500 text-slate-950 border-2 border-emerald-300 shadow-[0_0_8px_rgba(16,185,129,0.5)]')
                                    : (item.status === 'current'
                                        ? 'bg-amber-400 text-slate-950 border-2 border-amber-200 shadow-[0_0_12px_rgba(245,158,11,0.85)] scale-110'
                                        : (item.is_safe_point
                                            ? 'bg-amber-950 border-2 border-amber-400 text-amber-300 shadow-[0_0_8px_rgba(245,158,11,0.35)]'
                                            : 'bg-slate-800/90 border border-slate-700 text-slate-400'))"
                            >
                                <template x-if="item.status === 'completed'">
                                    <span x-text="item.is_correct === false ? '✗' : '✓'" class="text-xs font-black"></span>
                                </template>
                                <template x-if="item.status !== 'completed'">
                                    <span x-text="item.level"></span>
                                </template>
                            </div>

                            <!-- Label Nomor Level atau Safe Point di Bawah Lingkaran -->
                            <template x-if="item.is_safe_point">
                                <span class="mt-1 px-1 py-0.5 rounded bg-amber-400 text-slate-950 text-[8px] font-black leading-none uppercase tracking-tight">
                                    Safe Point
                                </span>
                            </template>
                            <template x-if="!item.is_safe_point">
                                <span
                                    class="mt-1 text-[10px] font-semibold leading-none"
                                    :class="item.status === 'current' ? 'text-amber-300 font-black' : 'text-slate-400'"
                                    x-text="item.level"
                                ></span>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- B. PANGGUNG TENGAH: KOTAK SOAL HEKSAGONAL & 4 TOMBOL OPSI HEKSAGONAL -->
        <div class="flex-1 px-3.5 py-2 flex flex-col justify-center gap-3 overflow-y-auto">
            <template x-if="state.session.status === 'ongoing' && state.question">
                <div class="space-y-3 my-auto">
                    <!-- Banner Notifikasi Feedback Transisi -->
                    <div
                        x-show="feedbackBanner"
                        x-transition.opacity.duration.200ms
                        x-cloak
                        class="px-3.5 py-2 rounded-xl border text-xs font-bold flex items-center justify-between"
                        :class="feedbackType === 'correct'
                            ? 'bg-emerald-950/90 border-emerald-400 text-emerald-200'
                            : 'bg-rose-950/90 border-rose-400 text-rose-200'"
                    >
                        <span x-text="feedbackBanner"></span>
                        <span class="text-[10px] uppercase opacity-80">Lanjut...</span>
                    </div>

                    <!-- Kotak Pertanyaan Heksagonal Berpendar Cyan (#38bdf8) -->
                    <div class="relative flex items-center justify-center my-1">
                        <div class="stage-line absolute -inset-x-3.5 top-1/2 -translate-y-1/2 -z-10"></div>
                        <div class="w-full hex-question-outer bg-[#38bdf8] p-[2.5px]">
                            <div class="hex-question-inner bg-gradient-to-b from-[#0b1329] via-[#070c1b] to-[#0b1329] px-6 py-6 text-center">
                                <p
                                    class="text-base sm:text-lg font-bold text-white leading-snug tracking-tight"
                                    x-text="state.question.question_text"
                                ></p>
                            </div>
                        </div>
                    </div>

                    <!-- 4 Tombol Opsi Jawaban Heksagonal Vertikal (A, B, C, D) -->
                    <div class="space-y-2.5 pt-1">
                        <template x-for="optKey in ['A', 'B', 'C', 'D']" :key="'mob-opt-' + optKey">
                            <div class="relative flex items-center">
                                <!-- Garis Horizontal Panggung di Belakang Tombol -->
                                <div
                                    class="absolute -inset-x-3.5 top-1/2 -translate-y-1/2 -z-10 transition-all"
                                    :class="selectedOption === optKey ? 'stage-line-amber' : 'stage-line'"
                                ></div>

                                <button
                                    type="button"
                                    :disabled="isSubmitting || isOptionEliminated(optKey)"
                                    @click="selectOption(optKey)"
                                    class="w-full hex-outer p-[2px] text-left focus:outline-none group cursor-pointer active:scale-[0.99] disabled:cursor-not-allowed"
                                    :class="mobileOptionOuterClass(optKey)"
                                >
                                    <div
                                        class="hex-inner px-5 py-3.5 flex items-center justify-between gap-2.5"
                                        :class="mobileOptionInnerClass(optKey)"
                                    >
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <span
                                                class="text-sm font-black shrink-0"
                                                :class="selectedOption === optKey ? 'text-amber-300' : 'text-amber-400'"
                                                x-text="optKey + ':'"
                                            ></span>

                                            <span
                                                class="text-sm font-bold text-white leading-snug break-words"
                                                :class="isOptionEliminated(optKey) ? 'line-through opacity-30' : ''"
                                                x-text="state.question.options[optKey]"
                                            ></span>
                                        </div>

                                        <!-- Badge TERKUNCI sesuai Mockup -->
                                        <span
                                            x-show="selectedOption === optKey && !revealedCorrectOption"
                                            class="shrink-0 text-[9px] font-black uppercase tracking-wider px-2.5 py-1 rounded-md bg-amber-950/90 text-amber-300 border border-amber-400/50"
                                        >
                                            TERKUNCI
                                        </span>

                                        <!-- Badge 50:50 -->
                                        <span
                                            x-show="isOptionEliminated(optKey)"
                                            class="shrink-0 text-[9px] font-bold uppercase px-2 py-0.5 rounded bg-slate-900 text-slate-500"
                                        >
                                            50:50
                                        </span>
                                    </div>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <!-- Tampilan Mobile Saat Eliminasi (Game Over) -->
            <template x-if="state.session.status === 'eliminated'">
                <div class="flex flex-col items-center justify-center text-center my-auto py-8 px-4 bg-[#0f172a]/90 border border-rose-500/40 rounded-3xl">
                    <div class="w-16 h-16 rounded-full bg-rose-500/20 border-2 border-rose-500 flex items-center justify-center text-3xl mb-3">
                        ✖
                    </div>
                    <span class="px-3 py-1 rounded-full bg-rose-500/20 text-rose-300 text-[10px] font-black uppercase tracking-widest">
                        DISKUALIFIKASI • POIN 0
                    </span>
                    <h2 class="text-2xl font-black text-white mt-2">GAME OVER</h2>
                    <p class="text-slate-300 text-xs mt-1">
                        Poin Anda menyentuh 0 pada Level <span x-text="state.session.current_step"></span>.
                    </p>
                    <div class="grid grid-cols-3 gap-2 w-full my-5 bg-slate-900/90 border border-rose-500/30 rounded-2xl p-3">
                        <div>
                            <div class="text-[10px] text-slate-400">Nilai Akhir</div>
                            <div class="text-xl font-black text-rose-400" x-text="state.session.current_score"></div>
                        </div>
                        <div>
                            <div class="text-[10px] text-slate-400">Benar</div>
                            <div class="text-xl font-black text-emerald-400" x-text="state.session.total_correct"></div>
                        </div>
                        <div>
                            <div class="text-[10px] text-slate-400">Salah</div>
                            <div class="text-xl font-black text-rose-400" x-text="state.session.total_wrong"></div>
                        </div>
                    </div>
                    <a :href="routes.loginUrl" class="w-full py-3 rounded-xl bg-sky-500 text-slate-950 font-black text-xs uppercase tracking-wider">
                        Kembali ke Portal Siswa
                    </a>
                </div>
            </template>

            <!-- Tampilan Mobile Saat Selesai (Finished) -->
            <template x-if="state.session.status === 'finished'">
                <div class="flex flex-col items-center justify-center text-center my-auto py-8 px-4 bg-[#0f172a]/90 border border-amber-400/40 rounded-3xl">
                    <div class="w-16 h-16 rounded-full bg-amber-500/20 border-2 border-amber-400 flex items-center justify-center text-3xl mb-3">
                        🏆
                    </div>
                    <span class="px-3 py-1 rounded-full bg-amber-500/20 border border-amber-400/50 text-amber-300 text-[10px] font-black tracking-widest">
                        Ujian Online SELESAI !!!
                    </span>
                    <h2 class="text-2xl font-black text-amber-300 mt-2">
                        SELAMAT, <span x-text="state.student.full_name"></span>!
                    </h2>
                    <div class="grid grid-cols-2 gap-2.5 w-full my-5 bg-slate-900/90 border border-sky-400/30 rounded-2xl p-3">
                        <div>
                            <div class="text-[10px] text-slate-400">Skor Akhir</div>
                            <div class="text-xl font-black text-amber-400" x-text="state.session.current_score"></div>
                        </div>
                        <div>
                            <div class="text-[10px] text-slate-400">Safe Point</div>
                            <div class="text-xl font-black text-sky-300" x-text="state.session.safe_score"></div>
                        </div>
                        <div>
                            <div class="text-[10px] text-slate-400">Benar</div>
                            <div class="text-xl font-black text-emerald-400" x-text="state.session.total_correct"></div>
                        </div>
                        <div>
                            <div class="text-[10px] text-slate-400">Salah</div>
                            <div class="text-xl font-black text-rose-400" x-text="state.session.total_wrong"></div>
                        </div>
                    </div>
                    <a :href="routes.loginUrl" class="w-full py-3 rounded-xl bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-black text-xs uppercase tracking-wider">
                        Selesai &amp; Kembali ke Beranda
                    </a>
                </div>
            </template>
        </div>

        <!-- C. STICKY BOTTOM BAR BERGAYA HEKSAGONAL (⚡ 50:50 & KUNCI JAWABAN & LANJUT >) -->
        <template x-if="state.session.status === 'ongoing' && state.question">
            <div
                :class="mobileMockupMode && !isMobileViewport
                    ? 'mt-auto bg-[#070b19]/95 border-t border-sky-400/30 px-3.5 py-3 flex items-center gap-2.5'
                    : 'fixed bottom-0 inset-x-0 z-30 bg-[#070b19]/95 backdrop-blur-md border-t border-sky-400/30 px-3.5 py-3 flex items-center justify-center gap-2.5 shadow-[0_-10px_30px_rgba(7,11,25,0.95)]'"
            >
                <div class="w-full max-w-md flex items-center gap-2.5">
                    <!-- Tombol Heksagonal ⚡ 50:50 di Kiri -->
                    <button
                        type="button"
                        @click="triggerLifeline5050()"
                        :disabled="state.session.lifeline_5050_used || isSubmitting"
                        class="shrink-0 hex-action-outer p-[1.5px] cursor-pointer disabled:cursor-not-allowed active:scale-95"
                        :class="state.session.lifeline_5050_used ? 'bg-slate-700 opacity-50' : 'bg-[#38bdf8] glow-cyan'"
                    >
                        <div class="hex-action-inner bg-[#0b1329] px-5 py-3 flex items-center justify-center gap-1.5 text-xs sm:text-sm font-black text-white">
                            <span class="text-amber-400">⚡</span>
                            <span>50:50</span>
                        </div>
                    </button>

                    <!-- Tombol Heksagonal KUNCI JAWABAN & LANJUT > di Kanan -->
                    <button
                        type="button"
                        @click="submitSelectedAnswer(false)"
                        :disabled="!selectedOption || isSubmitting"
                        class="flex-1 hex-action-outer p-[1.5px] cursor-pointer disabled:cursor-not-allowed active:scale-[0.99]"
                        :class="selectedOption && !isSubmitting
                            ? 'bg-amber-300 glow-amber'
                            : 'bg-slate-700'"
                    >
                        <div
                            class="hex-action-inner px-5 py-3 flex items-center justify-center gap-1.5 text-xs sm:text-sm font-black uppercase tracking-wider"
                            :class="selectedOption && !isSubmitting
                                ? 'bg-gradient-to-r from-[#f59e0b] via-amber-400 to-[#f59e0b] text-slate-950'
                                : 'bg-slate-800/90 text-slate-500'"
                        >
                            <span x-show="!isSubmitting">KUNCI JAWABAN &amp; LANJUT &gt;</span>
                            <span x-show="isSubmitting" x-cloak>MEMPROSES...</span>
                        </div>
                    </button>
                </div>
            </div>
        </template>

        <!-- D. SLIDE-UP DRAWER / MODAL TANGGA TINGKAT SOAL (15 LEVEL) UNTUK SMARTPHONE -->
        <div
            x-show="showMobileLadderDrawer"
            x-transition.opacity
            x-cloak
            class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-end sm:items-center justify-center p-0 sm:p-4"
        >
            <div
                @click.outside="showMobileLadderDrawer = false"
                class="w-full max-w-md bg-[#0f172a] border-t-2 sm:border-2 border-sky-400/50 rounded-t-3xl sm:rounded-3xl p-5 max-h-[82vh] flex flex-col shadow-[0_-10px_40px_rgba(56,189,248,0.25)]"
            >
                <div class="flex items-center justify-between border-b border-slate-800 pb-3 mb-3">
                    <div>
                        <h3 class="text-sm font-black uppercase tracking-wider text-sky-300">TANGGA TINGKAT SOAL (15 LEVEL)</h3>
                        <p class="text-[11px] text-slate-400">Level 5 &amp; 10 adalah Titik Aman [AMAN]</p>
                    </div>
                    <button
                        type="button"
                        @click="showMobileLadderDrawer = false"
                        class="px-3 py-1 rounded-xl bg-slate-800 text-slate-200 text-xs font-bold cursor-pointer"
                    >
                        ✕ Tutup
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto space-y-1.5 pr-1">
                    <template x-for="item in reversedLadder()" :key="'drawer-lvl-' + item.level">
                        <div
                            class="flex items-center justify-between px-3.5 py-2 rounded-xl border text-xs font-bold"
                            :class="ladderItemClass(item)"
                        >
                            <div class="flex items-center gap-2">
                                <span class="w-5 text-right font-mono font-black" x-text="item.level"></span>
                                <span class="text-slate-500">◆</span>
                                <span x-text="'Level ' + item.level"></span>
                                <template x-if="item.is_safe_point">
                                    <span class="px-1.5 py-0.5 rounded bg-amber-500/25 border border-amber-400/60 text-amber-300 text-[9px] font-black">
                                        [AMAN]
                                    </span>
                                </template>
                            </div>
                            <div>
                                <template x-if="item.status === 'completed'">
                                    <span
                                        class="font-black"
                                        :class="item.is_correct === false ? 'text-rose-400' : 'text-emerald-400'"
                                        x-text="item.is_correct === false ? '[✗]' : '[✓]'"
                                    ></span>
                                </template>
                                <template x-if="item.status === 'current'">
                                    <span class="px-2 py-0.5 rounded bg-amber-400 text-slate-950 text-[10px] font-black uppercase">
                                        ◄ AKTIF
                                    </span>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. TAMPILAN DESKTOP PENUH (Aktif di layar >= lg saat Mockup Mode OFF)     -->
    <!-- ========================================================================= -->
    <div
        x-show="!isMobileViewport && !mobileMockupMode"
        class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-4 flex-1 flex flex-col justify-between gap-6"
    >
        <!-- ================= TOP BAR: IDENTITAS, POIN SAAT INI & SISA WAKTU ================= -->
        <header class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-stretch">
            <!-- Box Identitas Siswa & Sesi Ujian -->
            <div class="lg:col-span-5 bg-[#0f172a]/90 border border-sky-400/40 rounded-2xl p-4 shadow-[0_0_25px_rgba(56,189,248,0.12)] flex flex-col justify-between">
                <div class="flex items-center justify-between gap-2">
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-widest bg-sky-500/15 text-sky-300 border border-sky-400/30">
                        <span class="w-2 h-2 rounded-full bg-sky-400 animate-pulse"></span>
                        {{ config('app.name', 'Ujian Online') }}
                    </span>
                    <span
                        class="text-xs font-semibold px-2.5 py-1 rounded-md border"
                        :class="state.session.timer_mode === 'mode_a'
                            ? 'bg-indigo-500/20 text-indigo-300 border-indigo-400/40'
                            : 'bg-amber-500/20 text-amber-300 border-amber-400/40'"
                        x-text="state.exam.timer_mode_label"
                    ></span>
                </div>

                <div class="mt-3">
                    <h2 class="text-lg sm:text-xl font-extrabold tracking-tight text-white truncate" x-text="state.exam.title"></h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Peserta: <span class="text-sky-300 font-semibold" x-text="state.student.full_name"></span>
                        (<span x-text="state.student.nis"></span> • <span x-text="state.student.class_name"></span>)
                    </p>
                </div>
            </div>

            <!-- Box "Poin Saat Ini" (Modal 20 + Net Poin & Safe Point) -->
            <div class="lg:col-span-4 bg-[#0f172a]/95 border-2 border-sky-400/60 rounded-2xl p-4 shadow-[0_0_30px_rgba(56,189,248,0.2)] flex flex-col justify-between relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-widest text-sky-300">POIN SAAT INI</span>
                    <span
                        class="text-[11px] font-bold px-2.5 py-0.5 rounded-full border border-amber-400/50 bg-amber-500/15 text-amber-300"
                        title="Nilai dasar terkunci pada titik aman"
                    >
                        SAFE POINT: <span x-text="state.session.safe_score"></span> PTS
                    </span>
                </div>

                <div class="my-1 flex items-baseline gap-3">
                    <span
                        class="text-3xl sm:text-4xl font-black tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-sky-300 via-cyan-200 to-amber-300"
                        x-text="state.session.current_score"
                    ></span>
                    <span class="text-xs font-semibold text-slate-400 uppercase">Poin Total</span>
                </div>

                <div class="flex items-center justify-between text-xs border-t border-slate-800 pt-2 text-slate-300">
                    <span>Modal Awal: <strong class="text-white" x-text="state.session.starting_score"></strong></span>
                    <span>
                        Net Poin:
                        <strong
                            :class="state.session.net_score >= 0 ? 'text-emerald-400' : 'text-rose-400'"
                            x-text="(state.session.net_score >= 0 ? '+' : '') + state.session.net_score"
                        ></strong>
                    </span>
                </div>
            </div>

            <!-- Box "Sisa Waktu" (Countdown Reaktif Alpine.js) -->
            <div
                class="lg:col-span-3 bg-[#0f172a]/95 border-2 rounded-2xl p-4 flex flex-col justify-between transition-colors duration-300"
                :class="timerRemaining <= 10 && state.session.status === 'ongoing'
                    ? 'border-rose-500 shadow-[0_0_30px_rgba(244,63,94,0.4)] animate-pulse'
                    : 'border-sky-400/60 shadow-[0_0_30px_rgba(56,189,248,0.2)]'"
            >
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-widest text-sky-300">SISA WAKTU</span>
                    <span
                        class="text-[11px] uppercase font-semibold px-2 py-0.5 rounded bg-slate-800 text-slate-300"
                        x-text="state.session.timer_mode === 'mode_a' ? 'Per Soal' : 'Global'"
                    ></span>
                </div>

                <div class="my-1 flex items-baseline justify-between">
                    <span
                        class="text-3xl sm:text-4xl font-mono font-black tracking-wider"
                        :class="timerRemaining <= 10 ? 'text-rose-400' : 'text-amber-400'"
                        x-text="formattedTimer()"
                    ></span>
                    <span class="text-xs text-slate-400" x-show="state.session.timer_mode === 'mode_a'">detik</span>
                </div>

                <!-- Progress Bar Timer -->
                <div class="w-full bg-slate-800/90 h-2 rounded-full overflow-hidden">
                    <div
                        class="h-full transition-all duration-300 rounded-full"
                        :class="timerRemaining <= 10 ? 'bg-rose-500' : 'bg-gradient-to-r from-sky-400 to-amber-400'"
                        :style="`width: ${timerPercentage()}%`"
                    ></div>
                </div>
            </div>
        </header>

        <!-- ================= MAIN STAGE & RIGHT SIDEBAR (15-LEVEL LADDER) ================= -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start flex-1">

            <!-- KIRI / TENGAH: PANGGUNG KUIS MILLIONAIRE (8 Kolom) -->
            <main class="lg:col-span-8 flex flex-col justify-between bg-[#0f172a]/80 border border-sky-500/30 rounded-3xl p-5 sm:p-7 shadow-[0_0_50px_rgba(7,11,25,0.9)] relative overflow-hidden min-h-[520px]">

                <!-- Spotlight Dekoratif -->
                <div class="pointer-events-none absolute -top-24 left-1/2 -translate-x-1/2 w-96 h-48 bg-sky-400/10 blur-3xl rounded-full"></div>

                <!-- TAMPILAN SAAT SESI BERLANGSUNG (ONGOING) -->
                <template x-if="state.session.status === 'ongoing' && state.question">
                    <div class="flex flex-col justify-between h-full gap-6 relative z-10">

                        <!-- Info Header Level Soal & Bobot -->
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800/80 pb-4">
                            <div class="flex items-center gap-3">
                                <span class="px-3.5 py-1.5 rounded-xl bg-sky-500/20 border border-sky-400/50 text-sky-300 font-extrabold text-sm tracking-wide">
                                    LEVEL <span x-text="state.question.level_tier"></span> / <span x-text="state.session.max_level"></span>
                                </span>

                                <template x-if="state.question.is_safe_point">
                                    <span class="px-3 py-1 rounded-xl bg-amber-500/20 border border-amber-400 text-amber-300 font-extrabold text-xs tracking-wider uppercase shadow-[0_0_15px_rgba(245,158,11,0.3)]">
                                        ★ SOAL TITIK AMAN [AMAN]
                                    </span>
                                </template>
                            </div>

                            <div class="flex items-center gap-3 text-xs font-bold">
                                <span class="px-2.5 py-1 rounded-lg bg-emerald-500/15 border border-emerald-500/30 text-emerald-300">
                                    Benar: +<span x-text="state.question.score_gain"></span> Poin
                                </span>
                                <span class="px-2.5 py-1 rounded-lg bg-rose-500/15 border border-rose-500/30 text-rose-300">
                                    Salah / Waktu Habis: -<span x-text="state.question.score_penalty"></span> Poin
                                </span>
                            </div>
                        </div>

                        <!-- Banner Notifikasi Feedback Transisi -->
                        <div
                            x-show="feedbackBanner"
                            x-transition.opacity.duration.250ms
                            x-cloak
                            class="px-4 py-3 rounded-xl border text-sm font-bold flex items-center justify-between"
                            :class="feedbackType === 'correct'
                                ? 'bg-emerald-950/90 border-emerald-400 text-emerald-200 shadow-[0_0_20px_rgba(16,185,129,0.3)]'
                                : 'bg-rose-950/90 border-rose-400 text-rose-200 shadow-[0_0_20px_rgba(244,63,94,0.3)]'"
                        >
                            <span x-text="feedbackBanner"></span>
                            <span class="text-xs uppercase tracking-wider opacity-80">Memuat soal berikutnya...</span>
                        </div>

                        <!-- KOTAK PERTANYAAN BERGAYA HEKSAGONAL DENGAN CYAN GLOW (#38bdf8) -->
                        <div class="relative flex items-center justify-center my-2">
                            <div class="stage-line absolute inset-x-0 top-1/2 -translate-y-1/2 -z-10"></div>

                            <div class="w-full hex-question-outer bg-[#38bdf8] p-[2px]">
                                <div class="hex-question-inner bg-gradient-to-b from-[#0f172a] via-[#091024] to-[#0f172a] px-8 sm:px-12 py-7 sm:py-9 text-center">
                                    <p
                                        class="text-base sm:text-xl font-bold text-white leading-relaxed tracking-wide"
                                        x-text="state.question.question_text"
                                    ></p>
                                </div>
                            </div>
                        </div>

                        <!-- 4 TOMBOL OPSI JAWABAN BERGAYA HEKSAGONAL (A, B, C, D) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5 my-2">
                            <template x-for="optKey in ['A', 'B', 'C', 'D']" :key="optKey">
                                <div class="relative flex items-center">
                                    <!-- Garis Penghubung Panggung -->
                                    <div
                                        class="hidden md:block absolute inset-x-0 top-1/2 -translate-y-1/2 -z-10 transition-all"
                                        :class="selectedOption === optKey ? 'stage-line-amber' : 'stage-line'"
                                    ></div>

                                    <button
                                        type="button"
                                        :disabled="isSubmitting || isOptionEliminated(optKey)"
                                        @click="selectOption(optKey)"
                                        class="w-full hex-outer p-[2px] text-left focus:outline-none group cursor-pointer disabled:cursor-not-allowed"
                                        :class="optionOuterClass(optKey)"
                                    >
                                        <div
                                            class="hex-inner px-7 py-4 flex items-center justify-between gap-3"
                                            :class="optionInnerClass(optKey)"
                                        >
                                            <div class="flex items-center gap-3 min-w-0">
                                                <!-- Diamond & Huruf Opsi -->
                                                <span
                                                    class="text-sm font-black shrink-0 flex items-center gap-1.5"
                                                    :class="selectedOption === optKey ? 'text-slate-950' : 'text-amber-400 group-hover:text-slate-950'"
                                                >
                                                    <span>◆</span>
                                                    <span x-text="optKey + ':'"></span>
                                                </span>

                                                <!-- Teks Opsi -->
                                                <span
                                                    class="text-sm sm:text-base font-semibold truncate"
                                                    :class="isOptionEliminated(optKey) ? 'line-through opacity-30' : ''"
                                                    x-text="state.question.options[optKey]"
                                                ></span>
                                            </div>

                                            <!-- Badge Status "TERKUNCI" Saat Diklik / Dipilih -->
                                            <span
                                                x-show="selectedOption === optKey && !revealedCorrectOption"
                                                class="shrink-0 text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded bg-slate-950/80 text-amber-300 border border-amber-300/40"
                                            >
                                                TERKUNCI
                                            </span>

                                            <!-- Badge Eliminasi 50:50 -->
                                            <span
                                                x-show="isOptionEliminated(optKey)"
                                                class="shrink-0 text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-slate-900 text-slate-500"
                                            >
                                                50:50
                                            </span>
                                        </div>
                                    </button>
                                </div>
                            </template>
                        </div>

                        <!-- BAR TOMBOL AKSI: "Bantuan 50:50 (1x)" & "Kunci Jawaban & Lanjut >" -->
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 pt-4 border-t border-slate-800/80">
                            <!-- Tombol Bantuan 50:50 (1x) -->
                            <button
                                type="button"
                                @click="triggerLifeline5050()"
                                :disabled="state.session.lifeline_5050_used || isSubmitting"
                                class="px-5 py-3 rounded-xl font-extrabold text-sm tracking-wide border-2 transition-all flex items-center justify-center gap-2 cursor-pointer disabled:cursor-not-allowed"
                                :class="state.session.lifeline_5050_used
                                    ? 'bg-slate-900/60 border-slate-700 text-slate-500 opacity-60'
                                    : 'bg-indigo-950/80 border-sky-400 text-sky-200 hover:bg-sky-500 hover:text-slate-950 shadow-[0_0_20px_rgba(56,189,248,0.25)]'"
                            >
                                <span>⚡ Bantuan 50:50 (1x)</span>
                                <span
                                    class="text-[10px] px-2 py-0.5 rounded-full uppercase"
                                    :class="state.session.lifeline_5050_used ? 'bg-slate-800 text-slate-400' : 'bg-sky-400/20 text-sky-300'"
                                    x-text="state.session.lifeline_5050_used ? 'Terpakai' : 'Tersedia'"
                                ></span>
                            </button>

                            <!-- Tombol Kunci Jawaban & Lanjut > -->
                            <button
                                type="button"
                                @click="submitSelectedAnswer(false)"
                                :disabled="!selectedOption || isSubmitting"
                                class="px-7 py-3.5 rounded-xl font-black text-sm sm:text-base tracking-wider uppercase transition-all flex items-center justify-center gap-2 cursor-pointer disabled:cursor-not-allowed"
                                :class="selectedOption && !isSubmitting
                                    ? 'bg-gradient-to-r from-amber-500 via-amber-400 to-yellow-400 text-slate-950 border-2 border-amber-200 shadow-[0_0_25px_rgba(245,158,11,0.65)] hover:brightness-110'
                                    : 'bg-slate-800/80 border-2 border-slate-700 text-slate-500'"
                            >
                                <span x-show="!isSubmitting">Kunci Jawaban &amp; Lanjut &gt;</span>
                                <span x-show="isSubmitting" x-cloak>Memproses Jawaban...</span>
                            </button>
                        </div>
                    </div>
                </template>

                <!-- TAMPILAN SAAT ELIMINASI / GAME OVER (POIN MENYENTUH 0) -->
                <template x-if="state.session.status === 'eliminated'">
                    <div class="flex flex-col items-center justify-center text-center my-auto py-10 px-4 relative z-10">
                        <div class="w-20 h-20 rounded-full bg-rose-500/20 border-2 border-rose-500 flex items-center justify-center text-4xl mb-4 shadow-[0_0_40px_rgba(244,63,94,0.5)]">
                            ✖
                        </div>
                        <span class="px-4 py-1 rounded-full bg-rose-500/20 border border-rose-400/40 text-rose-300 text-xs font-black uppercase tracking-widest">
                            DISKUALIFIKASI • POIN MENYENTUH 0
                        </span>
                        <h2 class="text-3xl sm:text-4xl font-black text-white mt-3 tracking-tight">
                            GAME OVER — TERELIMINASI
                        </h2>
                        <p class="text-slate-300 max-w-md mt-2 text-sm">
                            Poin Anda telah habis menyentuh angka <strong>0</strong> pada Level <span x-text="state.session.current_step"></span>. Sesuai aturan Millionaire CBT, sesi ujian otomatis dihentikan.
                        </p>

                        <div class="grid grid-cols-3 gap-4 w-full max-w-md my-6 bg-slate-900/90 border border-rose-500/30 rounded-2xl p-4">
                            <div>
                                <div class="text-xs text-slate-400">Nilai Akhir</div>
                                <div class="text-2xl font-black text-rose-400" x-text="state.session.current_score"></div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-400">Jawaban Benar</div>
                                <div class="text-2xl font-black text-emerald-400" x-text="state.session.total_correct"></div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-400">Jawaban Salah</div>
                                <div class="text-2xl font-black text-rose-400" x-text="state.session.total_wrong"></div>
                            </div>
                        </div>

                        <a
                            :href="routes.loginUrl"
                            class="px-6 py-3 rounded-xl bg-sky-500 hover:bg-sky-400 text-slate-950 font-black text-sm uppercase tracking-wider transition shadow-[0_0_20px_rgba(56,189,248,0.4)]"
                        >
                            Kembali ke Portal Siswa
                        </a>
                    </div>
                </template>

                <!-- TAMPILAN SAAT UJIAN SELESAI (FINISHED) -->
                <template x-if="state.session.status === 'finished'">
                    <div class="flex flex-col items-center justify-center text-center my-auto py-10 px-4 relative z-10">
                        <div class="w-20 h-20 rounded-full bg-amber-500/20 border-2 border-amber-400 flex items-center justify-center text-4xl mb-4 shadow-[0_0_40px_rgba(245,158,11,0.5)]">
                            🏆
                        </div>
                        <span class="px-4 py-1 rounded-full bg-amber-500/20 border border-amber-400/50 text-amber-300 text-xs font-black tracking-widest">
                            Ujian Online SELESAI !!!
                        </span>
                        <h2 class="text-3xl sm:text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-amber-300 via-yellow-200 to-sky-300 mt-3">
                            SELAMAT, <span x-text="state.student.full_name"></span>!
                        </h2>
                        <p class="text-slate-300 max-w-md mt-2 text-sm">
                            Anda telah menyelesaikan seluruh tahapan kuis dengan hasil akhir yang tercatat di bawah ini.
                        </p>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 w-full max-w-xl my-6 bg-slate-900/90 border border-sky-400/40 rounded-2xl p-4">
                            <div>
                                <div class="text-xs text-slate-400">Skor Akhir</div>
                                <div class="text-2xl font-black text-amber-400" x-text="state.session.current_score"></div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-400">Titik Aman</div>
                                <div class="text-2xl font-black text-sky-300" x-text="state.session.safe_score"></div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-400">Benar</div>
                                <div class="text-2xl font-black text-emerald-400" x-text="state.session.total_correct"></div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-400">Salah</div>
                                <div class="text-2xl font-black text-rose-400" x-text="state.session.total_wrong"></div>
                            </div>
                        </div>

                        <a
                            :href="routes.loginUrl"
                            class="px-6 py-3 rounded-xl bg-gradient-to-r from-amber-400 to-yellow-500 hover:brightness-110 text-slate-950 font-black text-sm uppercase tracking-wider transition shadow-[0_0_25px_rgba(245,158,11,0.5)]"
                        >
                            Selesai &amp; Kembali ke Beranda
                        </a>
                    </div>
                </template>
            </main>

            <!-- KANAN: SIDEBAR TANGGA TINGKAT SOAL (LEVEL 1 SAMPAI 15) -->
            <aside class="lg:col-span-4 bg-[#0f172a]/90 border border-sky-400/40 rounded-3xl p-5 shadow-[0_0_35px_rgba(7,11,25,0.9)]">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3 mb-3">
                    <div>
                        <h2 class="text-sm font-black uppercase tracking-wider text-sky-300">TANGGA TINGKAT SOAL</h2>
                        <p class="text-[11px] text-slate-400">Level 5 &amp; 10 adalah Titik Aman [AMAN]</p>
                    </div>
                    <span class="text-xs font-bold px-2.5 py-1 rounded-lg bg-slate-800 text-amber-300">
                        15 Level
                    </span>
                </div>

                <!-- Daftar Tangga Level 15 (Atas) ke Level 1 (Bawah) -->
                <div class="flex flex-col gap-1.5">
                    <template x-for="item in reversedLadder()" :key="item.level">
                        <div
                            class="flex items-center justify-between px-3.5 py-2 rounded-xl border text-xs font-bold transition-all"
                            :class="ladderItemClass(item)"
                        >
                            <div class="flex items-center gap-2.5">
                                <!-- Nomor Level -->
                                <span
                                    class="w-6 text-right font-mono font-black"
                                    :class="item.is_safe_point ? 'text-amber-300' : ''"
                                    x-text="item.level"
                                ></span>

                                <span class="text-slate-500">◆</span>

                                <!-- Label Level -->
                                <span x-text="'Level ' + item.level"></span>

                                <!-- Badge Titik Aman [AMAN] -->
                                <template x-if="item.is_safe_point">
                                    <span class="px-2 py-0.5 rounded bg-amber-500/25 border border-amber-400/60 text-amber-300 text-[10px] font-black tracking-wider">
                                        [AMAN]
                                    </span>
                                </template>
                            </div>

                            <!-- Status Level: Selesai [✓], Aktif, atau Upcoming -->
                            <div class="flex items-center gap-2">
                                <template x-if="item.status === 'completed'">
                                    <span
                                        class="font-black tracking-wider"
                                        :class="item.is_correct === false ? 'text-rose-400' : 'text-emerald-400'"
                                        x-text="item.is_correct === false ? '[✗]' : '[✓]'"
                                    ></span>
                                </template>

                                <template x-if="item.status === 'current'">
                                    <span class="px-2 py-0.5 rounded bg-amber-400 text-slate-950 text-[10px] font-black uppercase tracking-wider">
                                        ◄ AKTIF
                                    </span>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </aside>
        </div>
    </div>
</div>

<script>
    function examArena(initialState, routes) {
        return {
            state: initialState,
            routes: routes,
            selectedOption: null,
            eliminatedOptions: [],
            isSubmitting: false,
            timerRemaining: 30,
            timerMax: 30,
            questionElapsedSec: 0,
            timerInterval: null,
            feedbackBanner: '',
            feedbackType: 'correct',
            revealedCorrectOption: null,
            mobileMockupMode: false,
            isMobileViewport: window.innerWidth < 1024,
            showMobileLadderDrawer: false,

            initArena() {
                this.setupTimerForCurrentState();
                this.scrollToActiveMobileLevel();
                window.addEventListener('resize', () => {
                    this.isMobileViewport = window.innerWidth < 1024;
                });
            },

            toggleMobileMockup(enable) {
                this.mobileMockupMode = enable;
                if (enable) {
                    setTimeout(() => this.scrollToActiveMobileLevel(), 100);
                }
            },

            scrollToActiveMobileLevel() {
                setTimeout(() => {
                    const container = this.$refs.mobileTimeline;
                    if (!container) {
                        return;
                    }
                    const currentLvl = this.state.question ? this.state.question.level_tier : this.state.session.current_step;
                    const activeEl = container.querySelector(`[data-level="${currentLvl}"]`);
                    if (activeEl) {
                        activeEl.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
                    }
                }, 80);
            },

            setupTimerForCurrentState() {
                if (this.timerInterval) {
                    clearInterval(this.timerInterval);
                    this.timerInterval = null;
                }

                if (this.state.session.status !== 'ongoing' || !this.state.question) {
                    this.timerRemaining = 0;
                    return;
                }

                this.questionElapsedSec = 0;

                if (this.state.session.timer_mode === 'mode_a') {
                    const qDuration = parseInt(this.state.question.duration_sec || this.state.session.default_question_timer || 30, 10);
                    this.timerMax = qDuration;
                    this.timerRemaining = qDuration;
                } else {
                    const globalTotal = parseInt(this.state.session.total_global_timer || 1200, 10);
                    const globalRem = parseInt(this.state.session.remaining_global_sec ?? globalTotal, 10);
                    this.timerMax = globalTotal;
                    this.timerRemaining = Math.max(0, globalRem);
                }

                this.timerInterval = setInterval(() => {
                    if (this.isSubmitting || this.state.session.status !== 'ongoing') {
                        return;
                    }

                    this.questionElapsedSec++;

                    if (this.timerRemaining > 0) {
                        this.timerRemaining--;
                    }

                    if (this.timerRemaining <= 0) {
                        clearInterval(this.timerInterval);
                        this.timerInterval = null;
                        this.handleTimerExpired();
                    }
                }, 1000);
            },

            formattedTimer() {
                const sec = Math.max(0, parseInt(this.timerRemaining || 0, 10));
                if (this.state.session.timer_mode === 'mode_a') {
                    return String(sec).padStart(2, '0');
                }
                const minutes = Math.floor(sec / 60);
                const seconds = sec % 60;
                return String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
            },

            timerPercentage() {
                if (!this.timerMax || this.timerMax <= 0) {
                    return 0;
                }
                return Math.min(100, Math.max(0, Math.round((this.timerRemaining / this.timerMax) * 100)));
            },

            reversedLadder() {
                return [...(this.state.ladder || [])].reverse();
            },

            selectOption(optKey) {
                if (this.isSubmitting || this.isOptionEliminated(optKey) || this.state.session.status !== 'ongoing') {
                    return;
                }
                this.selectedOption = optKey;
            },

            isOptionEliminated(optKey) {
                return this.eliminatedOptions.includes(optKey);
            },

            mobileOptionOuterClass(optKey) {
                if (this.isOptionEliminated(optKey)) {
                    return 'bg-slate-800 opacity-40';
                }
                if (this.revealedCorrectOption) {
                    if (this.revealedCorrectOption === optKey) {
                        return 'bg-emerald-400 glow-emerald';
                    }
                    if (this.selectedOption === optKey && this.selectedOption !== this.revealedCorrectOption) {
                        return 'bg-rose-500 glow-rose';
                    }
                }
                if (this.selectedOption === optKey) {
                    return 'bg-[#f59e0b] glow-amber';
                }
                return 'bg-[#38bdf8]/80 glow-cyan';
            },

            mobileOptionInnerClass(optKey) {
                if (this.isOptionEliminated(optKey)) {
                    return 'bg-[#090d1c] text-slate-600';
                }
                if (this.revealedCorrectOption) {
                    if (this.revealedCorrectOption === optKey) {
                        return 'bg-emerald-700 text-white';
                    }
                    if (this.selectedOption === optKey && this.selectedOption !== this.revealedCorrectOption) {
                        return 'bg-rose-800 text-white';
                    }
                }
                if (this.selectedOption === optKey) {
                    return 'bg-gradient-to-r from-[#452708] via-[#6b3c0a] to-[#361d05] text-white';
                }
                return 'bg-gradient-to-r from-[#0d162d] via-[#091022] to-[#0d162d] text-slate-100';
            },

            optionOuterClass(optKey) {
                if (this.isOptionEliminated(optKey)) {
                    return 'bg-slate-800 opacity-40';
                }
                if (this.revealedCorrectOption) {
                    if (this.revealedCorrectOption === optKey) {
                        return 'bg-emerald-400 glow-emerald';
                    }
                    if (this.selectedOption === optKey && this.selectedOption !== this.revealedCorrectOption) {
                        return 'bg-rose-500 glow-rose';
                    }
                }
                if (this.selectedOption === optKey) {
                    return 'bg-[#f59e0b] glow-amber';
                }
                return 'bg-[#38bdf8]/80 hover:bg-[#f59e0b] glow-cyan';
            },

            optionInnerClass(optKey) {
                if (this.isOptionEliminated(optKey)) {
                    return 'bg-[#090d1c] text-slate-600';
                }
                if (this.revealedCorrectOption) {
                    if (this.revealedCorrectOption === optKey) {
                        return 'bg-emerald-600 text-white';
                    }
                    if (this.selectedOption === optKey && this.selectedOption !== this.revealedCorrectOption) {
                        return 'bg-rose-700 text-white';
                    }
                }
                if (this.selectedOption === optKey) {
                    return 'bg-gradient-to-r from-[#f59e0b] via-amber-400 to-[#f59e0b] text-slate-950 font-bold';
                }
                return 'bg-gradient-to-r from-[#0f172a] via-[#0b1329] to-[#0f172a] text-slate-100 group-hover:from-[#f59e0b] group-hover:to-amber-400 group-hover:text-slate-950';
            },

            ladderItemClass(item) {
                if (item.status === 'current') {
                    return 'bg-gradient-to-r from-amber-500/30 to-amber-400/10 border-amber-400 text-amber-200 shadow-[0_0_15px_rgba(245,158,11,0.3)] scale-[1.01]';
                }
                if (item.status === 'completed') {
                    return item.is_safe_point
                        ? 'bg-amber-950/30 border-amber-500/40 text-amber-200'
                        : 'bg-slate-900/70 border-sky-500/20 text-slate-300';
                }
                if (item.is_safe_point) {
                    return 'bg-slate-900/80 border-amber-500/40 text-amber-300';
                }
                return 'bg-slate-950/50 border-slate-800/80 text-slate-400';
            },

            async handleTimerExpired() {
                if (this.state.session.timer_mode === 'mode_a') {
                    // Mode A: Timer per soal habis -> otomatis dianggap salah (-5 poin) & lompat ke soal berikutnya
                    await this.submitSelectedAnswer(true, false);
                } else {
                    // Mode B: Timer global habis -> auto-submit jawaban (jika ada) & selesaikan sesi
                    if (this.selectedOption) {
                        await this.submitSelectedAnswer(false, true);
                    } else {
                        await this.triggerGlobalTimeoutSubmit();
                    }
                }
            },

            async submitSelectedAnswer(isTimeout = false, isGlobalTimeout = false) {
                if (this.isSubmitting || !this.state.question) {
                    return;
                }

                this.isSubmitting = true;

                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    const response = await fetch(this.routes.answerUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            question_id: this.state.question.id,
                            chosen_opt: isTimeout ? null : this.selectedOption,
                            time_spent_sec: this.questionElapsedSec,
                            is_timeout: isTimeout,
                            is_global_timeout: isGlobalTimeout
                        })
                    });

                    const result = await response.json();

                    if (!response.ok) {
                        if (result.state) {
                            this.state = result.state;
                        }
                        this.isSubmitting = false;
                        return;
                    }

                    // Tampilkan visual reveal opsi benar/salah sesaat sebelum transisi
                    this.revealedCorrectOption = result.feedback?.correct_opt || null;
                    this.feedbackType = result.feedback?.is_correct ? 'correct' : 'wrong';
                    this.feedbackBanner = result.message || '';

                    setTimeout(() => {
                        this.state = result.state;
                        this.selectedOption = null;
                        this.eliminatedOptions = [];
                        this.revealedCorrectOption = null;
                        this.feedbackBanner = '';
                        this.isSubmitting = false;

                        this.setupTimerForCurrentState();
                        this.scrollToActiveMobileLevel();
                    }, 650);
                } catch (error) {
                    console.error('Gagal mengirim jawaban:', error);
                    this.isSubmitting = false;
                }
            },

            async triggerGlobalTimeoutSubmit() {
                this.isSubmitting = true;
                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    const response = await fetch(this.routes.globalTimeoutUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({})
                    });
                    const result = await response.json();
                    if (result.state) {
                        this.state = result.state;
                    }
                } catch (error) {
                    console.error('Gagal melakukan auto-submit global:', error);
                } finally {
                    this.isSubmitting = false;
                }
            },

            async triggerLifeline5050() {
                if (this.state.session.lifeline_5050_used || this.isSubmitting || !this.state.question) {
                    return;
                }

                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    const response = await fetch(this.routes.lifelineUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            question_id: this.state.question.id
                        })
                    });

                    const result = await response.json();
                    if (response.ok) {
                        this.state.session.lifeline_5050_used = true;
                        this.eliminatedOptions = result.eliminated_options || [];
                        if (this.selectedOption && this.eliminatedOptions.includes(this.selectedOption)) {
                            this.selectedOption = null;
                        }
                    }
                } catch (error) {
                    console.error('Gagal mengaktifkan bantuan 50:50:', error);
                }
            }
        };
    }
</script>
</body>
</html>
