<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'user_id' => User::factory(),
            'creator_id' => User::factory(),
            'status' => 'pending',
            'priority' => 'medium',
            'due_at' => now()->addDays(2),
            'sla_breached' => false,
            'metadata' => ['department' => 'Operations'],
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completed_at' => now(),
            'action_notes' => 'Task completed successfully',
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'due_at' => now()->subDay(),
            'sla_breached' => true,
        ]);
    }

    public function delegated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delegated',
            'delegated_to_id' => User::factory(),
            'delegated_by_id' => User::factory(),
            'delegated_at' => now(),
        ]);
    }
}
