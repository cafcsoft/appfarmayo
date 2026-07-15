<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Restablecer Contraseña')]
class ForgotPassword extends Component
{
    public int $step = 1;

    public string $username = '';

    public string $recovery_pin = '';

    public int $user_id;

    public string $password = '';

    public string $password_confirmation = '';

    public function verifyPin(): void
    {
        $this->validate([
            'username' => 'required|string',
            'recovery_pin' => 'required|string',
        ]);

        $user = User::where(function ($query) {
            $query->where('username', $this->username)
                  ->orWhere('ruc_cedula', $this->username);
        })
        ->where('recovery_pin', $this->recovery_pin)
        ->first();

        if (! $user) {
            $this->addError('recovery_pin', __('El usuario o el PIN de recuperación es incorrecto.'));

            return;
        }

        $this->user_id = $user->id;
        $this->step = 2;
    }

    public function resetPassword()
    {
        $this->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::findOrFail($this->user_id);
        $user->password = Hash::make($this->password);
        $user->save();

        session()->flash('status', __('¡Tu contraseña ha sido restablecida con éxito! Ya puedes iniciar sesión con la nueva clave.'));

        return $this->redirectRoute('login', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.forgot-password')
            ->layout('layouts.auth', ['title' => __('Restablecer Contraseña')]);
    }
}
