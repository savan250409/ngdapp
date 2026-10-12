<?php

namespace Database\Seeders;

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
        // Static admin account (the only user who can access the panel).
        User::updateOrCreate(
            ['email' => 'admin@ngdapp'],
            [
                'name'     => 'Admin',
                'password' => Hash::make('ngdapp@2026'),
            ]
        );
    }
}
