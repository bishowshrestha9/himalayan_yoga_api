<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (!User::where('email', 'admin@admin.com')->exists()) {
            User::create([
                'name' => 'Admi 2',
                'email' => 'admin2@admin.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]);
        } else {
            Log::info("Admin already exists");
        }
    }
}
