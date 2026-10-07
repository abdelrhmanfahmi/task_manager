<?php

namespace Database\Seeders;

use App\Enums\TaskPriority as Priority;
use App\Enums\TaskStatus as Status;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Creates sample tasks for the accounts made by UserSeeder.
 * Tasks are matched by owner + title, so re-running adds no duplicates.
 */
class TaskSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->tasksByEmail() as $email => $tasks) {
            $user = User::where('email', $email)->firstOrFail();

            foreach ($tasks as $task) {
                $user->tasks()->firstOrCreate(['title' => $task['title']], $task);
            }
        }
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private function tasksByEmail(): array
    {
        return [
            UserSeeder::JOHN_EMAIL => [
                ['title' => 'Set up project repository', 'description' => 'Initialise Git, add README and folder structure.', 'priority' => Priority::High, 'status' => Status::Completed, 'due_date' => today()->subDays(10)],
                ['title' => 'Design database schema', 'description' => 'Users and tasks tables with proper keys and indexes.', 'priority' => Priority::High, 'status' => Status::Completed, 'due_date' => today()->subDays(8)],
                ['title' => 'Build login page', 'description' => 'Email + password form with session-based authentication.', 'priority' => Priority::High, 'status' => Status::Completed, 'due_date' => today()->subDays(6)],
                ['title' => 'Write feature tests', 'description' => 'Cover authentication, authorization and validation.', 'priority' => Priority::Medium, 'status' => Status::InProgress, 'due_date' => today()->addDays(2)],
                ['title' => 'Responsive dashboard', 'description' => 'Make the task table collapse into cards on mobile.', 'priority' => Priority::Medium, 'status' => Status::Completed, 'due_date' => today()->subDays(3)],
                ['title' => 'Code review', 'description' => 'Review the pull request from the frontend team.', 'priority' => Priority::Low, 'status' => Status::Pending, 'due_date' => today()->addDay()],
                ['title' => 'Update documentation', 'description' => null, 'priority' => Priority::Low, 'status' => Status::Pending, 'due_date' => today()->addWeek()],
                ['title' => 'Fix invoice rounding bug', 'description' => 'Totals are rounded incorrectly on the invoice PDF.', 'priority' => Priority::High, 'status' => Status::Pending, 'due_date' => today()->subDay()],
                ['title' => 'Plan sprint retrospective', 'description' => 'Collect feedback and prepare the agenda.', 'priority' => Priority::Medium, 'status' => Status::Completed, 'due_date' => today()->subDays(2)],
                ['title' => 'Deploy to staging', 'description' => 'Run migrations and smoke-test the main flows.', 'priority' => Priority::Medium, 'status' => Status::Pending, 'due_date' => null],
            ],
            UserSeeder::JANE_EMAIL => [
                ['title' => 'Prepare quarterly report', 'description' => 'Only Jane can see this task.', 'priority' => Priority::High, 'status' => Status::Pending, 'due_date' => today()->addDays(5)],
                ['title' => 'Book team offsite', 'description' => null, 'priority' => Priority::Low, 'status' => Status::Completed, 'due_date' => today()->subDays(4)],
            ],
        ];
    }
}
