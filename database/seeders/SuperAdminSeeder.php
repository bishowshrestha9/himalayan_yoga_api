<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin1@example.com',
            'role' => 'super_admin',
            'password' => \Hash::make('12345678'), // Default password
            
        ]);
    }
}
