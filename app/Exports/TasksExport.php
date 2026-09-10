<?php

namespace App\Exports;

use App\Models\Task;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TasksExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(protected ?Collection $tasks = null) {}

    /**
     * @return Collection<int, Task>
     */
    public function collection(): Collection
    {
        return $this->tasks ?? Task::with('user')->latest()->get();
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'ID',
            'UUID',
            'Title',
            'Assignee',
            'Status',
            'Priority',
            'Due Date',
            'Completed At',
            'SLA Breached',
            'Created At',
        ];
    }

    /**
     * @param  Task  $task
     * @return array<int, mixed>
     */
    public function map($task): array
    {
        return [
            $task->id,
            $task->uuid,
            $task->title,
            $task->user?->name ?? 'Unassigned',
            $task->status,
            $task->priority,
            $task->due_at?->toIso8601String(),
            $task->completed_at?->toIso8601String(),
            $task->sla_breached ? 'Yes' : 'No',
            $task->created_at?->toIso8601String(),
        ];
    }
}
