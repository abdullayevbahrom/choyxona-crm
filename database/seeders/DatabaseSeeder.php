<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Setting::query()->updateOrCreate(
            ['id' => 1],
            [
                'company_name' => 'Choyxona CRM',
                'company_address' => 'Toshkent shahri',
                'company_phone' => '+998 90 000 00 00',
                'receipt_footer' => 'Xaridingiz uchun rahmat!',
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'admin@choyxona.uz'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
                'email_verified_at' => now(),
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'manager@choyxona.uz'],
            [
                'name' => 'Manager',
                'password' => Hash::make('password'),
                'role' => User::ROLE_MANAGER,
                'email_verified_at' => now(),
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'cashier@choyxona.uz'],
            [
                'name' => 'Cashier',
                'password' => Hash::make('password'),
                'role' => User::ROLE_CASHIER,
                'email_verified_at' => now(),
            ],
        );
    }
}
