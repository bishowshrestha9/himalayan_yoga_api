<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Reviews;
use App\Models\Service;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = Service::all();

        if ($services->isEmpty()) {
            $this->command->info('No services found. Please seed services first.');
            return;
        }

        $reviews = [
            [
                'name' => 'Bishow Shrestha',
                'email' => 'bishow.shrestha@example.com',
                'review' => 'Absolutely transformative experience! The instructor was knowledgeable and created a welcoming atmosphere. I feel more connected to my body and mind.',
                'rating' => 5,
                'status' => true,
            ],
            [
                'name' => 'Dishant Giri',
                'email' => 'dishant.giri@example.com',
                'review' => 'Great class! The breathing techniques and poses were explained clearly. Perfect for beginners like me.',
                'rating' => 5,
                'status' => true,
            ],
            [
                'name' => 'Sudarshan Gharti',
                'email' => 'sudarshan.gharti@example.com',
                'review' => 'I have been practicing for 3 months now and the results are amazing. My flexibility has improved significantly and I feel less stressed.',
                'rating' => 4,
                'status' => true,
            ],
            [
                'name' => 'Shiridhar Khatri',
                'email' => 'shiridhar.khatri@example.com',
                'review' => 'The meditation sessions are incredibly calming. The instructor guides us with patience and expertise.',
                'rating' => 5,
                'status' => true,
            ],
            [
                'name' => 'Dipshan Chapagain',
                'email' => 'dipshan.chapagain@example.com',
                'review' => 'Wonderful experience! The studio has a peaceful ambiance and the classes are well-structured.',
                'rating' => 4,
                'status' => true,
            ],
            [
                'name' => 'Grishma Kafle',
                'email' => 'grishma.kafle@example.com',
                'review' => 'Very good class but the timing could be more flexible. Overall satisfied with the instruction quality.',
                'rating' => 4,
                'status' => true,
            ],
            [
                'name' => 'Abhishek Adhikari',
                'email' => 'abhishek.adhikari@example.com',
                'review' => 'Life-changing journey! Himalayan Yoga has helped me find inner peace and balance in my hectic life.',
                'rating' => 5,
                'status' => true,
            ],
            [
                'name' => 'Piyush Thapa',
                'email' => 'piyush.thapa@example.com',
                'review' => 'Good for stress relief. The instructor is friendly and supportive. Would recommend to anyone looking to start yoga.',
                'rating' => 4,
                'status' => true,
            ],
            [
                'name' => 'Sevika Shahi',
                'email' => 'sevika.shahi@example.com',
                'review' => 'Amazing experience! Every session leaves me feeling rejuvenated and centered. The ancient techniques are truly powerful.',
                'rating' => 5,
                'status' => true,
            ],
            [
                'name' => 'Arun Rai',
                'email' => 'arun.rai@example.com',
                'review' => 'Excellent instruction and beautiful practice space. The holistic approach to wellness is exactly what I needed.',
                'rating' => 5,
                'status' => true,
            ],
        ];

        foreach ($reviews as $reviewData) {
            // Randomly assign to a service
            $reviewData['service_id'] = $services->random()->id;
            Reviews::create($reviewData);
        }

        $this->command->info('Reviews seeded successfully!');
    }
}
