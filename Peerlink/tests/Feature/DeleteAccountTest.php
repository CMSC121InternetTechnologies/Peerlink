<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// US_04: As a user, I want to safely delete my account and associated data.
class DeleteAccountTest extends TestCase
{
    use RefreshDatabase;

    private function seedProgram(): void
    {
        DB::table('Programs')->insert([
            'program_code' => 'BSCS',
            'program_name' => 'Bachelor of Science in Computer Science',
        ]);
    }

    public function test_delete_account_requires_authentication(): void
    {
        $response = $this->delete('/profile', ['password' => 'password']);

        $response->assertRedirect('/login');
    }

    public function test_user_can_delete_account_with_correct_password(): void
    {
        $this->seedProgram();
        $user = User::factory()->create(['program_code' => 'BSCS']);

        $response = $this->actingAs($user)->delete('/profile', ['password' => 'password']);

        $response->assertRedirect('/');
        $this->assertGuest();
        $this->assertDatabaseMissing('Users', ['email' => $user->email]);
    }

    public function test_delete_account_fails_with_wrong_password(): void
    {
        $this->seedProgram();
        $user = User::factory()->create(['program_code' => 'BSCS']);

        $response = $this->actingAs($user)->delete('/profile', ['password' => 'wrong-password']);

        $response->assertSessionHasErrorsIn('userDeletion', 'password');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('Users', ['email' => $user->email]);
    }

    public function test_delete_account_requires_password_field(): void
    {
        $this->seedProgram();
        $user = User::factory()->create(['program_code' => 'BSCS']);

        $response = $this->actingAs($user)->delete('/profile', ['password' => '']);

        $response->assertSessionHasErrorsIn('userDeletion', 'password');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('Users', ['email' => $user->email]);
    }

    public function test_user_is_logged_out_after_account_deletion(): void
    {
        $this->seedProgram();
        $user = User::factory()->create(['program_code' => 'BSCS']);
        $userId = $user->user_id;

        $this->actingAs($user)->delete('/profile', ['password' => 'password']);

        $this->assertGuest();
        $this->assertDatabaseMissing('Users', ['user_id' => $userId]);
    }
}
