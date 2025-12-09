<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Instructor;


class InstructorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Instructor::create([
            'name' => 'John Doe',
            'profession' => 'Yoga Instructor',
            'experience' => 5,
            'bio' => 'John has been teaching yoga for over 5 years...',
            'specialities' => 'Hatha, Vinyasa',
            'certifications' => ['RYT-200', 'RYT-500', 'CPR'],
            'image' => 'instructors/john_doe.jpg',
        ]);
    }
}
