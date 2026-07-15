<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyFeature(Features::registration());
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register(): void
    {
        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite') {
            \Illuminate\Support\Facades\DB::statement('CREATE TABLE IF NOT EXISTS "farmacia.client" (IDCLIENTE VARCHAR(255))');
        }

        \Illuminate\Support\Facades\DB::table('farmacia.client')->insert([
            'IDCLIENTE' => '1234567'
        ]);

        $response = $this->post(route('register.store'), [
            'name' => 'John Doe',
            'username' => 'johndoe',
            'ruc_cedula' => '1234567',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }
}
