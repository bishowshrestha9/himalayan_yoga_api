<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Reviews;

class ReviewsSeeder extends Seeder
{
    public function run(): void
    {
        Reviews::insert([
            [
                'name' => 'Anil Thapa',
                'email' => 'anil.thapa@example.com',
                'review' => 'The Himalayan Sunrise Yoga was a life-changing experience. The view and the instructor were amazing!',
                'rating' => 5,
                'status' => 1, // approved
                'service_id' => 1,
            ],
            [
                'name' => 'Sita Lama',
                'email' => 'sita.lama@example.com',
                'review' => 'Loved the Kathmandu Evening Meditation. It helped me find inner peace after a busy day.',
                'rating' => 4,
                'status' => 1, // approved
                'service_id' => 2,
            ],
            [
                'name' => 'Bikash Karki',
                'email' => 'bikash.karki@example.com',
                'review' => 'Pokhara Yoga Retreat was very relaxing and the group was friendly. Highly recommended!',
                'rating' => 5,
                'status' => 1, // approved
                'service_id' => 3,
            ],
        ]);
    }
}
