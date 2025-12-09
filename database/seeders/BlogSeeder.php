<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Blogs;
use Illuminate\Support\Facades\Storage;

class BlogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $blogs = [
            [
                'title' => 'Introduction to Himalayan Yoga',
                'excerpt' => 'Discover the ancient practice of Himalayan Yoga and its transformative benefits for mind, body, and spirit.',
                'description' => 'Himalayan Yoga is a profound spiritual practice that originated in the sacred mountains of the Himalayas. This ancient tradition combines physical postures, breathing techniques, meditation, and philosophical teachings to create a holistic approach to wellness. Practitioners learn to connect with their inner self while building physical strength and flexibility. The practice emphasizes mindfulness, self-awareness, and the development of a peaceful mind. Through regular practice, students experience improved health, reduced stress, and a deeper sense of purpose in life.',
                'image' => null,
                'slug' => 'introduction-to-himalayan-yoga',
                'is_active' => true,
            ],
            [
                'title' => 'Benefits of Daily Meditation Practice',
                'excerpt' => 'Learn how a consistent meditation practice can enhance your mental clarity and emotional well-being.',
                'description' => 'Daily meditation is one of the most powerful tools for achieving inner peace and mental clarity. Scientific research has shown that regular meditation practice can reduce anxiety, improve concentration, and enhance emotional health. It helps lower blood pressure, improves sleep quality, and boosts the immune system. Meditation teaches us to observe our thoughts without judgment, creating space between stimulus and response. This practice cultivates mindfulness in daily life, helping practitioners respond to challenges with greater wisdom and compassion. Even just 10-15 minutes of daily practice can yield significant benefits.',
                'image' => null,
                'slug' => 'benefits-of-daily-meditation-practice',
                'is_active' => true,
            ],
            [
                'title' => 'Pranayama: The Art of Breath Control',
                'excerpt' => 'Explore the ancient breathing techniques that form the foundation of yoga practice.',
                'description' => 'Pranayama, the yogic practice of breath control, is a cornerstone of traditional yoga. The word "pranayama" comes from "prana" (life force) and "ayama" (extension or expansion). These breathing techniques help regulate the flow of prana through the body, promoting physical and mental balance. Common practices include Nadi Shodhana (alternate nostril breathing), Kapalabhati (skull shining breath), and Ujjayi (victorious breath). Regular pranayama practice increases lung capacity, improves oxygen circulation, calms the nervous system, and prepares the mind for meditation. It is said that mastering the breath is the key to mastering the mind.',
                'image' => null,
                'slug' => 'pranayama-the-art-of-breath-control',
                'is_active' => true,
            ],
            [
                'title' => 'Yoga Philosophy: The Eight Limbs of Yoga',
                'excerpt' => 'Understanding the comprehensive path of yoga beyond physical postures.',
                'description' => 'The Eight Limbs of Yoga, outlined in Patanjali\'s Yoga Sutras, provide a complete framework for spiritual development. These limbs include: Yama (ethical standards), Niyama (self-discipline), Asana (physical postures), Pranayama (breath control), Pratyahara (sense withdrawal), Dharana (concentration), Dhyana (meditation), and Samadhi (spiritual absorption). Each limb builds upon the previous one, guiding practitioners from external ethical behavior to the innermost states of consciousness. Understanding these eight limbs transforms yoga from mere physical exercise into a comprehensive spiritual practice that touches every aspect of life.',
                'image' => null,
                'slug' => 'yoga-philosophy-the-eight-limbs-of-yoga',
                'is_active' => true,
            ],
            [
                'title' => 'Mindful Eating and Yogic Diet',
                'excerpt' => 'Discover how conscious eating habits support your yoga practice and overall health.',
                'description' => 'The yogic approach to nutrition emphasizes sattvic foods - those that are pure, fresh, and nutritious. This includes fresh fruits, vegetables, whole grains, legumes, nuts, and seeds. The yogic diet avoids processed foods, excessive spices, and foods that are overly stimulating or dulling. Beyond what we eat, yoga teaches us how to eat: slowly, mindfully, and with gratitude. Mindful eating involves paying full attention to the experience of eating, noticing flavors, textures, and the body\'s hunger and fullness cues. This practice promotes better digestion, prevents overeating, and creates a healthier relationship with food. When we eat with awareness, food becomes not just fuel but a form of self-care and spiritual practice.',
                'image' => null,
                'slug' => 'mindful-eating-and-yogic-diet',
                'is_active' => true,
            ],
        ];

        foreach ($blogs as $blog) {
            Blogs::create($blog);
        }
    }
}
