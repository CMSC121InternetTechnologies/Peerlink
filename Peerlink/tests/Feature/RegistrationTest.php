<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

// US_01: As a new user, I want to create a new account with a username and password.
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function seedProgram(): void
    {
        DB::table('Programs')->insert([
            'program_code' => 'BSCS',
            'program_name' => 'Bachelor of Science in Computer Science',
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name'            => 'Jane',
            'last_name'             => 'Doe',
            'program_code'          => 'BSCS',
            'current_year_level'    => 3,
            'email'                 => 'jane@up.edu.ph',
            'password'              => 'Password1!',
            'password_confirmation' => 'Password1!',
        ], $overrides);
    }

    public function test_registration_page_is_accessible(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_user_can_register_with_valid_data(): void
    {
        $this->seedProgram();

        $response = $this->post('/register', $this->validPayload());

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $this->assertDatabaseHas('Users', [
            'email'      => 'jane@up.edu.ph',
            'first_name' => 'Jane',
            'last_name'  => 'Doe',
            'program_code' => 'BSCS',
        ]);
    }

    public function test_password_is_stored_as_bcrypt_hash(): void
    {
        $this->seedProgram();

        $this->post('/register', $this->validPayload());

        $user = User::where('email', 'jane@up.edu.ph')->first();

        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('Password1!', $user->password_hash));
        $this->assertNotEquals('Password1!', $user->password_hash);
    }

    public function test_registration_requires_first_name(): void
    {
        $this->seedProgram();

        $response = $this->post('/register', $this->validPayload(['first_name' => '']));

        $response->assertSessionHasErrors('first_name');
        $this->assertGuest();
    }

    public function test_registration_requires_last_name(): void
    {
        $this->seedProgram();

        $response = $this->post('/register', $this->validPayload(['last_name' => '']));

        $response->assertSessionHasErrors('last_name');
        $this->assertGuest();
    }

    public function test_registration_requires_valid_program_code(): void
    {
        $this->seedProgram();

        $response = $this->post('/register', $this->validPayload(['program_code' => 'FAKECODE']));

        $response->assertSessionHasErrors('program_code');
        $this->assertGuest();
    }

    public function test_registration_requires_year_level(): void
    {
        $this->seedProgram();

        $response = $this->post('/register', $this->validPayload(['current_year_level' => '']));

        $response->assertSessionHasErrors('current_year_level');
        $this->assertGuest();
    }

    public function test_registration_rejects_year_level_below_one(): void
    {
        $this->seedProgram();

        $response = $this->post('/register', $this->validPayload(['current_year_level' => 0]));

        $response->assertSessionHasErrors('current_year_level');
        $this->assertGuest();
    }

    public function test_registration_requires_valid_email(): void
    {
        $this->seedProgram();

        $response = $this->post('/register', $this->validPayload(['email' => 'not-an-email']));

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        $this->seedProgram();
        User::factory()->create(['email' => 'jane@up.edu.ph', 'program_code' => 'BSCS']);

        $response = $this->post('/register', $this->validPayload());

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_registration_rejects_mismatched_password_confirmation(): void
    {
        $this->seedProgram();

        $response = $this->post('/register', $this->validPayload([
            'password'              => 'Password1!',
            'password_confirmation' => 'DifferentPassword1!',
        ]));

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_authenticated_user_cannot_access_register_page(): void
    {
        $this->seedProgram();
        $user = User::factory()->create(['program_code' => 'BSCS']);

        $response = $this->actingAs($user)->get('/register');

        $response->assertRedirect(route('dashboard'));
    }
}
