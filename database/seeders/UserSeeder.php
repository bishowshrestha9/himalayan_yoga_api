<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::insert([
            [
                'name' => 'Bishow Shrestha',
                'email' => 'bishow.shrestha@nepal.com',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ],
            [
                'name' => 'Pramila Rai',
                'email' => 'pramila.rai@nepal.com',
                'password' => Hash::make('password123'),
                'role' => 'user',
            ],
            [
                'name' => 'Kiran Maharjan',
                'email' => 'kiran.maharjan@nepal.com',
                'password' => Hash::make('password123'),
                'role' => 'user',
            ],
        ]);
    }
}
