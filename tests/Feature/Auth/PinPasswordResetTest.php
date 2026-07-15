<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PinPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertOk();
    }

    public function test_can_verify_pin_using_username(): void
    {
        $user = User::factory()->create([
            'username' => 'johndoe',
            'recovery_pin' => '123456',
        ]);

        Livewire::test(\App\Livewire\Auth\ForgotPassword::class)
            ->set('username', 'johndoe')
            ->set('recovery_pin', '123456')
            ->call('verifyPin')
            ->assertSet('step', 2)
            ->assertSet('user_id', $user->id)
            ->assertHasNoErrors();
    }

    public function test_can_verify_pin_using_ruc_cedula(): void
    {
        $user = User::factory()->create([
            'ruc_cedula' => '1234567',
            'recovery_pin' => '123456',
        ]);

        Livewire::test(\App\Livewire\Auth\ForgotPassword::class)
            ->set('username', '1234567')
            ->set('recovery_pin', '123456')
            ->call('verifyPin')
            ->assertSet('step', 2)
            ->assertSet('user_id', $user->id)
            ->assertHasNoErrors();
    }

    public function test_cannot_verify_with_incorrect_pin(): void
    {
        $user = User::factory()->create([
            'username' => 'johndoe',
            'recovery_pin' => '123456',
        ]);

        Livewire::test(\App\Livewire\Auth\ForgotPassword::class)
            ->set('username', 'johndoe')
            ->set('recovery_pin', 'wrong-pin')
            ->call('verifyPin')
            ->assertSet('step', 1)
            ->assertHasErrors(['recovery_pin']);
    }

    public function test_can_reset_password_after_pin_verification(): void
    {
        $user = User::factory()->create([
            'username' => 'johndoe',
            'recovery_pin' => '123456',
        ]);

        Livewire::test(\App\Livewire\Auth\ForgotPassword::class)
            ->set('username', 'johndoe')
            ->set('recovery_pin', '123456')
            ->call('verifyPin')
            ->assertSet('step', 2)
            ->set('password', 'new-password')
            ->set('password_confirmation', 'new-password')
            ->call('resetPassword')
            ->assertRedirect(route('login'));

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('new-password', $user->refresh()->password));
    }
}
