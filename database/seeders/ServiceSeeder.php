<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        Service::insert([
            [
                'title' => 'Himalayan Sunrise Yoga',
                'description' => 'Morning yoga sessions with a view of the Himalayas in Nagarkot.',
                'yoga_type' => 'basic',
                'benefits' => json_encode(['Flexibility', 'Stress Relief', 'Energy Boost']),
                'class_schedule' => json_encode(['Monday 6AM', 'Wednesday 6AM', 'Friday 6AM']),
                'instructor_id' => 1,
                'price' => 1200,
                'capacity' => 20,
                'images' => json_encode([]),
                'slug' => 'himalayan-sunrise-yoga',
                'is_active' => true,
            ],
            [
                'title' => 'Kathmandu Evening Meditation',
                'description' => 'Guided meditation classes in the heart of Kathmandu.',
                'yoga_type' => 'basic',
                'benefits' => json_encode(['Calmness', 'Focus', 'Inner Peace']),
                'class_schedule' => json_encode(['Tuesday 7PM', 'Thursday 7PM']),
                'instructor_id' => 2,
                'price' => 1000,
                'capacity' => 15,
                'images' => json_encode([]),
                'slug' => 'kathmandu-evening-meditation',
                'is_active' => true,
            ],
            [
                'title' => 'Pokhara Yoga Retreat',
                'description' => 'Weekend yoga retreat by Phewa Lake, Pokhara.',
                'yoga_type' => 'intermediate',
                'benefits' => json_encode(['Detox', 'Strength', 'Relaxation']),
                'class_schedule' => json_encode(['Saturday 8AM', 'Sunday 8AM']),
                'instructor_id' => 3,
                'price' => 2500,
                'capacity' => 25,
                'images' => json_encode([]),
                'slug' => 'pokhara-yoga-retreat',
                'is_active' => true,
            ],
        ]);
    }
}
