<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Provider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating users and providers...');

        // ایجاد کاربران معمولی
        $users = [
            [
                'phone' => '09123456789',
                'password' => Hash::make('password123'),
                'role' => 'user'
            ],
            [
                'phone' => '09111111111',
                'password' => Hash::make('password123'),
                'role' => 'user'
            ],
            [
                'phone' => '09222222222',
                'password' => Hash::make('password123'),
                'role' => 'user'
            ]
        ];

        foreach ($users as $userData) {
            User::create($userData);
            $this->command->info("User created: {$userData['phone']}");
        }

        // ایجاد کاربران provider
        $providers = [
            [
                'phone' => '09987654321',
                'name' => 'آرایشگاه مردانه علی',
                'lat' => 35.6892,
                'lng' => 51.3890,
                'is_online' => true
            ],
            [
                'phone' => '09333333333',
                'name' => 'تعمیرکار لوازم خانگی احمد',
                'lat' => 35.7000,
                'lng' => 51.4000,
                'is_online' => true
            ],
            [
                'phone' => '09444444444',
                'name' => 'نظافتچی حرفه‌ای فاطمه',
                'lat' => 35.6800,
                'lng' => 51.3800,
                'is_online' => false
            ],
            [
                'phone' => '09555555555',
                'name' => 'باربری و اتوبار محمد',
                'lat' => 35.7100,
                'lng' => 51.4100,
                'is_online' => true
            ],
            [
                'phone' => '09666666666',
                'name' => 'آشپز غذای خانگی مریم',
                'lat' => 35.6700,
                'lng' => 51.3700,
                'is_online' => true
            ],
            [
                'phone' => '09777777777',
                'name' => 'تعمیرکار موبایل حسن',
                'lat' => 35.6950,
                'lng' => 51.3950,
                'is_online' => false
            ],
            [
                'phone' => '09888888888',
                'name' => 'معلم خصوصی ریاضی سارا',
                'lat' => 35.6850,
                'lng' => 51.3850,
                'is_online' => true
            ]
        ];

        foreach ($providers as $providerData) {
            // ایجاد کاربر provider
            $user = User::create([
                'phone' => $providerData['phone'],
                'password' => Hash::make('password123'),
                'role' => 'provider'
            ]);

            // ایجاد رکورد provider
            Provider::create([
                'user_id' => $user->id,
                'name' => $providerData['name'],
                'lat' => $providerData['lat'],
                'lng' => $providerData['lng'],
                'is_online' => $providerData['is_online']
            ]);

            $this->command->info("Provider created: {$providerData['name']} ({$providerData['phone']})");
        }

        $this->command->info('All users and providers created successfully!');
    }
}