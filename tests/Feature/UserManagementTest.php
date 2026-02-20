<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_user_from_users_panel(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->actingAs($admin)->post('/users', [
            'name' => 'Operator',
            'email' => 'operator@example.com',
            'role' => User::ROLE_CASHIER,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'operator@example.com',
            'role' => User::ROLE_CASHIER,
        ]);
    }

    public function test_manager_cannot_create_user_from_users_panel(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
        ]);

        $response = $this->actingAs($manager)->post('/users', [
            'name' => 'Blocked',
            'email' => 'blocked@example.com',
            'role' => User::ROLE_CASHIER,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertForbidden();
    }
}
