<div class="min-h-screen bg-slate-900 flex items-center justify-center p-6 relative overflow-hidden">
    <!-- Decorative Blobs -->
    <div class="absolute -top-24 -left-24 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl animate-pulse"></div>
    <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-blue-600/20 rounded-full blur-3xl animate-pulse delay-1000"></div>

    <div class="max-w-5xl w-full bg-white rounded-[2.5rem] shadow-2xl flex overflow-hidden min-h-[600px] z-10 border border-white/20">
        <!-- Image Side -->
        <div class="hidden lg:block lg:w-1/2 relative">
            <img src="/artifacts/school_background_modern.png" class="absolute inset-0 w-full h-full object-cover" alt="School Background">
            <div class="absolute inset-0 bg-gradient-to-t from-indigo-900/80 via-transparent to-indigo-900/40"></div>
            <div class="absolute bottom-12 left-12 right-12 text-white">
                <h2 class="text-4xl font-bold mb-4 tracking-tight leading-tight">Membangun Masa Depan Cemerlang</h2>
                <p class="text-indigo-100 font-medium opacity-90">Sistem Informasi Akademik & Absensi Digital Terintegrasi SDN Cikampek Selatan 2.</p>
            </div>
        </div>

        <!-- Form Side -->
        <div class="w-full lg:w-1/2 p-12 lg:p-20 flex flex-col justify-center">
            <div class="mb-10 flex items-center space-x-3">
                <div class="w-12 h-12 bg-indigo-600 rounded-2xl flex items-center justify-center shadow-xl shadow-indigo-600/40">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">SIAKAD</h1>
                    <p class="text-[10px] uppercase tracking-[0.2em] font-bold text-slate-400">SDN CS 2</p>
                </div>
            </div>

            <h3 class="text-3xl font-bold text-slate-800 mb-2 tracking-tight">Selamat Datang</h3>
            <p class="text-slate-500 mb-8 font-medium">Silakan masuk ke akun Anda untuk melanjutkan.</p>

            <form wire:submit.prevent="login" class="space-y-6">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 px-1">Username</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400 group-focus-within:text-indigo-600 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206"></path></svg>
                        </span>
                        <input wire:model="username" type="text" autocomplete="username" placeholder="NIS / NIP" class="w-full pl-12 pr-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:ring-4 focus:ring-indigo-600/10 focus:border-indigo-600 transition font-medium text-slate-700">
                    </div>
                    @error('username') <span class="text-red-500 text-[10px] font-bold uppercase mt-1 ml-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 px-1">Password</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400 group-focus-within:text-indigo-600 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        </span>
                        <input wire:model="password" type="password" placeholder="••••••••" class="w-full pl-12 pr-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:ring-4 focus:ring-indigo-600/10 focus:border-indigo-600 transition font-medium text-slate-700">
                    </div>
                    @error('password') <span class="text-red-500 text-[10px] font-bold uppercase mt-1 ml-1">{{ $message }}</span> @enderror
                </div>

                <div class="flex items-center justify-between px-1">
                    <label class="flex items-center text-sm font-semibold text-slate-500 cursor-pointer">
                        <input type="checkbox" class="w-4 h-4 text-indigo-600 bg-slate-100 border-slate-300 rounded focus:ring-indigo-600">
                        <span class="ml-2">Ingat saya</span>
                    </label>
                    <a href="#" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 transition">Lupa password?</a>
                </div>

                <button type="submit" class="w-full py-4 bg-indigo-600 text-white rounded-2xl font-bold text-lg shadow-xl shadow-indigo-600/30 hover:bg-indigo-700 hover:-translate-y-0.5 active:translate-y-0 transition transform duration-200">
                    Masuk ke Sistem
                </button>
            </form>

            @if (session()->has('error'))
                <div class="mt-6 p-4 bg-red-50 rounded-2xl border border-red-100 text-red-600 text-sm font-bold flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    {{ session('error') }}
                </div>
            @endif
        </div>
    </div>
</div>
