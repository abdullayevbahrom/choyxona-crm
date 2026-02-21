<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsLogoUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_upload_logo_and_it_is_saved_as_png(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
        ]);

        Storage::fake('public');
        Setting::current();

        $response = $this->actingAs($manager)->patch(route('settings.update'), [
            'company_name' => 'Choyxona CRM',
            'company_address' => 'Toshkent',
            'company_phone' => '+998900000000',
            'receipt_footer' => 'Rahmat',
            'notification_from_name' => 'Choyxona CRM',
            'notification_from_email' => 'noreply@gmail.com',
            'notification_logo_size' => 24,
            'notification_logo_file' => UploadedFile::fake()->image(
                'logo.jpg',
                64,
                64,
            ),
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect();

        $setting = Setting::current();
        $this->assertNotNull($setting->notification_logo_url);
        $this->assertStringContainsString(
            '/storage/branding/qr-logos/',
            (string) $setting->notification_logo_url,
        );
        $this->assertStringEndsWith(
            '.png',
            (string) $setting->notification_logo_url,
        );

        $path = parse_url(
            (string) $setting->notification_logo_url,
            PHP_URL_PATH,
        );
        $relative = ltrim(str_replace('/storage/', '', (string) $path), '/');
        Storage::disk('public')->assertExists($relative);
    }
}
