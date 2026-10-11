<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Ujian Online') }} — Login Panel Guru &amp; Backoffice</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    @endif

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    <style>
        body {
            background-color: #070b19;
            background-image:
                radial-gradient(circle at 50% 0%, rgba(56, 189, 248, 0.20) 0%, transparent 55%),
                radial-gradient(circle at 85% 90%, rgba(245, 158, 11, 0.14) 0%, transparent 45%);
        }
    </style>
</head>
<body class="min-h-screen text-slate-100 antialiased flex flex-col justify-between">

<main class="max-w-lg w-full mx-auto px-4 sm:px-6 py-10 sm:py-16 my-auto" x-data="{ showPassword: false, username: '{{ old('username') }}', password: '' }">
    <!-- Header Badge -->
    <div class="text-center mb-7">
        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-sky-950/80 border border-sky-400/40 text-sky-300 text-xs font-extrabold tracking-widest uppercase mb-3 shadow-[0_0_25px_rgba(56,189,248,0.25)]">
            <span class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span>
            OTENTIKASI GURU &amp; BACKOFFICE CBT
        </div>
        <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
            Login <span class="text-transparent bg-clip-text bg-gradient-to-r from-sky-400 via-cyan-200 to-amber-400">Panel Guru &amp; Backoffice</span>
        </h1>
        <p class="mt-1.5 text-xs sm:text-sm text-slate-300">
            Masukkan Username dan Password yang terdaftar untuk mengakses manajemen ujian &amp; backoffice.
        </p>
    </div>

    <!-- Card Form Login -->
    <div class="bg-[#0f172a]/95 border-2 border-sky-400/50 rounded-3xl p-6 sm:p-8 shadow-[0_0_45px_rgba(56,189,248,0.2)]">
        @if (session('success'))
            <div class="mb-5 p-3.5 rounded-xl bg-emerald-950/90 border border-emerald-400/60 text-emerald-200 text-xs font-bold">
                ✓ {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-5 p-3.5 rounded-xl bg-rose-950/90 border border-rose-500/60 text-rose-200 text-xs font-semibold space-y-1">
                @foreach ($errors->all() as $error)
                    <div>• {{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form action="{{ route('login.submit') }}" method="POST" class="space-y-5">
            @csrf

            <div>
                <label for="username" class="block text-xs font-bold uppercase tracking-wider text-sky-300 mb-1.5">
                    Username Guru / Backoffice
                </label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    x-model="username"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="Masukkan username (contoh: supervisor atau admin)"
                    class="w-full px-4 py-3 rounded-xl bg-[#070b19] border border-sky-500/40 focus:border-amber-400 focus:outline-none text-white placeholder-slate-500 text-sm transition"
                >
            </div>

            <div>
                <label for="password" class="block text-xs font-bold uppercase tracking-wider text-sky-300 mb-1.5">
                    Password
                </label>
                <div class="relative">
                    <input
                        :type="showPassword ? 'text' : 'password'"
                        id="password"
                        name="password"
                        x-model="password"
                        required
                        autocomplete="current-password"
                        placeholder="Masukkan password..."
                        class="w-full pl-4 pr-20 py-3 rounded-xl bg-[#070b19] border border-sky-500/40 focus:border-amber-400 focus:outline-none text-white placeholder-slate-500 text-sm transition"
                    >
                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        class="absolute right-3 top-1/2 -translate-y-1/2 px-2.5 py-1 rounded-lg bg-slate-800/90 hover:bg-slate-700 text-[11px] font-bold text-sky-300 cursor-pointer"
                        x-text="showPassword ? 'Sembunyikan' : 'Lihat'"
                    ></button>
                </div>
            </div>

            <div class="flex items-center justify-between text-xs text-slate-400">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" value="1" class="rounded bg-slate-900 border-slate-700 text-sky-500">
                    <span>Ingat sesi login saya</span>
                </label>
                <a href="{{ route('exam.login') }}" class="text-sky-400 hover:text-sky-300 font-bold">
                    ← Kembali ke Portal Siswa
                </a>
            </div>

            <button
                type="submit"
                class="w-full py-3.5 px-6 rounded-xl bg-gradient-to-r from-amber-500 via-amber-400 to-yellow-400 hover:from-amber-400 hover:to-yellow-300 text-slate-950 font-black text-sm uppercase tracking-wider shadow-[0_0_25px_rgba(245,158,11,0.45)] transition cursor-pointer"
            >
                🔐 Masuk ke Panel Guru &amp; Backoffice &gt;
            </button>
        </form>

        <!-- Informasi Tingkat Akses & Akun Bawaan -->
        <div class="mt-6 pt-5 border-t border-slate-800 space-y-3">
            <div class="text-[11px] font-black uppercase tracking-wider text-slate-400">
                Tingkat Hak Akses Backoffice (Klik untuk isi cepat):
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs">
                <button
                    type="button"
                    @click="username = 'supervisor'; password = 'supervisor123'"
                    class="text-left p-3 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 border border-amber-400/40 transition cursor-pointer"
                >
                    <div class="flex items-center justify-between">
                        <span class="font-black text-amber-300 uppercase">👑 SUPERVISOR</span>
                        <span class="text-[10px] px-2 py-0.5 rounded bg-amber-400/20 text-amber-200 font-bold">Full Akses</span>
                    </div>
                    <div class="mt-1 font-mono text-[11px] text-slate-300">
                        User: <strong class="text-white">supervisor</strong><br>
                        Pass: <strong class="text-white">supervisor123</strong>
                    </div>
                    <div class="mt-1 text-[10px] text-slate-400">
                        Bisa tambah, edit, &amp; hapus semua data + kelola user.
                    </div>
                </button>

                <button
                    type="button"
                    @click="username = 'admin'; password = 'admin123'"
                    class="text-left p-3 rounded-xl bg-sky-500/10 hover:bg-sky-500/20 border border-sky-400/40 transition cursor-pointer"
                >
                    <div class="flex items-center justify-between">
                        <span class="font-black text-sky-300 uppercase">🛡️ ADMIN / GURU</span>
                        <span class="text-[10px] px-2 py-0.5 rounded bg-sky-400/20 text-sky-200 font-bold">No Delete</span>
                    </div>
                    <div class="mt-1 font-mono text-[11px] text-slate-300">
                        User: <strong class="text-white">admin</strong><br>
                        Pass: <strong class="text-white">admin123</strong>
                    </div>
                    <div class="mt-1 text-[10px] text-slate-400">
                        Full akses kelola ujian &amp; soal, <strong>tidak bisa delete data</strong>.
                    </div>
                </button>
            </div>
        </div>
    </div>
</main>

<footer class="py-4 text-center text-xs text-slate-500 border-t border-slate-900">
    {{ config('app.name', 'Ujian Online') }} CBT • Sistem Otentikasi Guru &amp; Backoffice
</footer>

</body>
</html>
