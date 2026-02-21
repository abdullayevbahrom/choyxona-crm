<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationResponsiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_navigation_uses_mobile_only_hamburger_and_shows_desktop_nav_from_sm(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();

        $response->assertSee('class="-me-2 flex items-center sm:hidden"', false);
        $response->assertSee('class="hidden sm:hidden"', false);
        $response->assertSee('hidden sm:-my-px sm:ms-4 sm:flex', false);
        $response->assertSee('hidden shrink-0 sm:ms-2 sm:flex sm:items-center', false);

        $response->assertDontSee('class="-me-2 flex items-center md:hidden"', false);
        $response->assertDontSee('class="hidden md:hidden"', false);
    }
}
