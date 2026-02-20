<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_errors_are_localized_in_uzbek(): void
    {
        app()->setLocale("uz");

        $manager = User::factory()->create([
            "role" => User::ROLE_MANAGER,
        ]);

        $response = $this->actingAs($manager)->post("/menu", []);
        $response->assertSessionHasErrors(["name"]);

        $message = session("errors")?->first("name");
        $this->assertIsString($message);
        $this->assertStringContainsString("to‘ldirilishi shart", $message);
    }
}
