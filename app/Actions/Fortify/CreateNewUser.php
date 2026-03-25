<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'ruc_cedula' => ['required', 'string', 'max:20'],
            'password' => $this->passwordRules(),
        ])->validate();

        $ruc = trim($input['ruc_cedula']);

        // Verify if ruc/id exists in client database
        $clientExists = DB::table('farmacia.client')
            ->where('IDCLIENTE', $ruc)
            ->orWhere('IDCLIENTE', $ruc)
            ->exists();

        if (! $clientExists) {
            throw ValidationException::withMessages([
                'ruc_cedula' => ['El número de RUC/Cédula no está registrado como cliente de la empresa.'],
            ]);
        }

        return User::create([
            'name' => $input['name'],
            'username' => $input['username'],
            'email' => $input['email'],
            'ruc_cedula' => $ruc,
            'is_customer' => true,
            'password' => $input['password'],
        ]);
    }
}
