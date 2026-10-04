<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Login extends Component
{
    public $username = '';

    public $password = '';

    protected $rules = [
        'username' => 'required|string|max:191',
        'password' => 'required',
    ];

    public function login()
    {
        $this->validate();

        $user = User::findByUsername($this->username);

        if ($user && Auth::attempt(['email' => $user->email, 'password' => $this->password])) {
            session()->regenerate();

            $role = Auth::user()->role;
            if ($role === 'admin') {
                return redirect()->intended('/admin/dashboard');
            } elseif ($role === 'guru') {
                return redirect()->intended('/guru/dashboard');
            } elseif ($role === 'ortu') {
                return redirect()->intended('/ortu/dashboard');
            } else {
                return redirect()->intended('/siswa/dashboard');
            }
        }

        $this->addError('username', 'Kredensial yang diberikan tidak cocok dengan data kami.');
    }

    public function render()
    {
        return view('livewire.auth.login')->layout('components.layouts.app');
    }
}
