<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Inquiry;

class InquirySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Inquiry::create([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '123-456-7890',
            'message' => 'I am interested in your yoga services. Please provide more information.',
            'service_id' => 1,
        ]);
    }
}
