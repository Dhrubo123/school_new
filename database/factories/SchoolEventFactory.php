<?php

namespace Database\Factories;

use App\Models\SchoolEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SchoolEventFactory extends Factory
{
    protected $model = SchoolEvent::class;

    public function definition(): array
    {
        $title = fake()->randomElement(['Annual Science & Innovation Fair', 'Inter-house Bangla Debate', 'Guardian Orientation Programme', 'Victory Day Cultural Programme', 'Book Reading Festival']);
        $starts = now()->addDays(fake()->numberBetween(2, 60))->setTime(10, 0);

        return ['author_id' => User::factory(), 'title' => $title, 'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(10, 9999), 'summary' => 'A purposeful campus programme bringing learners, educators and families together.', 'body' => 'The school community is warmly invited. Students should follow the instructions shared by their class teachers.', 'venue' => 'School Auditorium, Dhaka', 'starts_at' => $starts, 'ends_at' => $starts->copy()->addHours(3), 'status' => 'published', 'published_at' => now()->subDays(3)];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => 'draft', 'published_at' => null]);
    }
}
