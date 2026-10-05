<div class="flex min-h-screen items-center justify-center p-6">
    <div class="grid w-full max-w-5xl overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl lg:grid-cols-2">
        <div class="relative hidden bg-slate-900 lg:block">
            <img src="/artifacts/school_background_modern.png" alt=""
                 class="absolute inset-0 h-full w-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-slate-900/40 to-slate-900/20"></div>

            <div class="relative flex h-full flex-col justify-end p-10">
                <span class="mb-auto inline-flex h-10 w-10 items-center justify-center rounded-lg bg-brand-600 text-white">
                    <x-icon name="school" class="h-5 w-5" />
                </span>

                <h2 class="text-3xl font-semibold leading-tight tracking-tight text-white">
                    Membangun Masa Depan Cemerlang
                </h2>
                <p class="mt-3 text-sm leading-relaxed text-slate-300">
                    Sistem Informasi Akademik &amp; Absensi Digital Terintegrasi
                    {{ config('app.school_name') }}.
                </p>
            </div>
        </div>

        <div class="flex flex-col justify-center p-8 sm:p-12">
            <div class="mb-8 flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-600 text-white lg:hidden">
                    <x-icon name="book" class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-lg font-semibold tracking-tight text-slate-900">ADMS</p>
                    <p class="text-xs text-slate-500">{{ config('app.school_name') }}</p>
                </div>
            </div>

            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Masuk ke akun Anda</h1>
            <p class="mt-1.5 text-sm text-slate-500">Gunakan username dan password yang diberikan sekolah.</p>

            <form wire:submit.prevent="login" class="mt-7 space-y-5">
                <div>
                    <label for="username" class="label">Username</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <x-icon name="user" class="h-4 w-4" />
                        </span>
                        <input id="username" wire:model="username" type="text" autocomplete="username"
                               placeholder="NIS atau NIP"
                               class="input pl-9"
                               @class(['input-error' => $errors->has('username')])>
                    </div>
                    @error('username')
                        <p class="help-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="label">Password</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <x-icon name="lock" class="h-4 w-4" />
                        </span>
                        <input id="password" wire:model="password" type="password" autocomplete="current-password"
                               placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                               class="input pl-9"
                               @class(['input-error' => $errors->has('password')])>
                    </div>
                    @error('password')
                        <p class="help-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <label for="remember" class="flex cursor-pointer items-center gap-2 text-sm text-slate-600">
                    <input id="remember" wire:model="remember" type="checkbox"
                           class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-600">
                    Ingat saya
                </label>

                <button type="submit" class="btn btn-primary w-full py-2.5" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="login">Masuk</span>
                    <span wire:loading wire:target="login" class="inline-flex items-center gap-2">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                        </svg>
                        Memproses&hellip;
                    </span>
                </button>
            </form>

            <p class="mt-8 text-xs leading-relaxed text-slate-400">
                Lupa password atau belum punya akun? Hubungi administrator sekolah.
            </p>
        </div>
    </div>
</div>