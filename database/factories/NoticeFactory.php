<?php

namespace Database\Factories;

use App\Models\Notice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class NoticeFactory extends Factory
{
    protected $model = Notice::class;

    public function definition(): array
    {
        $title = fake()->randomElement(['Half-yearly examination schedule published', 'Parent–teacher meeting for Classes VI–X', 'Scholarship application window is now open', 'Campus closed for Durga Puja holiday', 'Science Olympiad registration notice']);

        return ['author_id' => User::factory(), 'title' => $title, 'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(10, 9999), 'summary' => 'Please review the schedule and complete the required steps within the announced deadline.', 'body' => 'Students and guardians are requested to read this notice carefully. For clarification, contact the school office during working hours.', 'status' => 'published', 'published_at' => now()->subDays(fake()->numberBetween(0, 20))];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => 'draft', 'published_at' => null]);
    }
}
