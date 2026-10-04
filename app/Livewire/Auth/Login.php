<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;

class Login extends Component
{
    /** Jumlah percobaan gagal sebelum dikunci. */
    private const MAKS_PERCOBAAN = 5;

    /** Lama penguncian dalam detik. */
    private const KUNCI_DETIK = 60;

    public string $username = '';

    public string $password = '';

    public bool $remember = false;

    protected $rules = [
        'username' => 'required|string|max:191',
        'password' => 'required',
    ];

    protected $validationAttributes = [
        'username' => 'username atau NIS/NIP',
    ];

    public function login()
    {
        $this->validate();

        $kunci = $this->kunciRateLimit();

        if ($kunci > 0) {
            $this->addError('username', "Terlalu banyak percobaan gagal. Coba lagi dalam {$kunci} detik.");

            return;
        }

        $user = User::findByUsername($this->username);

        // Password dibandingkan langsung dengan user hasil pencarian, bukan
        // lewat Auth::attempt berdasarkan email, supaya tidak pernah cocok
        // dengan akun lain yang kebetulan punya email sama.
        if ($user && Hash::check($this->password, $user->password)) {
            RateLimiter::clear($this->throttleKey());

            Auth::login($user, $this->remember);
            session()->regenerate();

            return redirect()->intended($this->setelahLogin($user->role));
        }

        RateLimiter::hit($this->throttleKey(), self::KUNCI_DETIK);

        $this->addError('username', 'Kredensial yang diberikan tidak cocok dengan data kami.');
    }

    /**
     * Halaman tujuan setelah login sesuai peran pengguna.
     */
    private function setelahLogin(string $role): string
    {
        return match ($role) {
            'admin' => '/admin/dashboard',
            'guru' => '/guru/dashboard',
            default => '/siswa/dashboard',
        };
    }

    /**
     * Sisa detik penguncian, atau 0 bila belum terkunci.
     */
    private function kunciRateLimit(): int
    {
        return RateLimiter::tooManyAttempts($this->throttleKey(), self::MAKS_PERCOBAAN)
            ? RateLimiter::availableIn($this->throttleKey())
            : 0;
    }

    /**
     * Kunci_rate_limiter dikunci per kombinasi IP dan username supaya satu
     * penyerang tidak bisa mengunci akun orang lain hanya dengan menyebut
     * banyak username.
     */
    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->username).'|'.request()->ip());
    }

    public function render()
    {
        return view('livewire.auth.login')->layout('components.layouts.guest', ['title' => 'Masuk']);
    }
}