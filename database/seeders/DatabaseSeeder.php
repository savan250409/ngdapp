<?php

namespace Database\Seeders;

use App\Models\App;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Default app.
        App::updateOrCreate(['code' => 'ngd001'], ['name' => 'NGD 001', 'status' => 1]);

        // Super-admin (no app_code = sees all apps, can switch).
        User::updateOrCreate(
            ['email' => 'admin@ngdapp'],
            [
                'name'     => 'Super Admin',
                'password' => Hash::make('ngdapp@2026'),
                'app_code' => null,
            ]
        );

        // App-scoped admin for ngd001.
        User::updateOrCreate(
            ['email' => 'ngd001@admin'],
            [
                'name'     => 'NGD001 Admin',
                'password' => Hash::make('ngd001@2026'),
                'app_code' => 'ngd001',
            ]
        );
    }
}
