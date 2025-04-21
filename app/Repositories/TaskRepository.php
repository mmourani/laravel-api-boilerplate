<?php

namespace App\Repositories;

use App\Contracts\TaskRepositoryInterface;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Collection;

class TaskRepository implements TaskRepositoryInterface
{
    public function all(Project $project, array $filters = [], ?string $sortBy = null, string $direction = 'asc'): Collection
    {
        $query = $project->tasks();

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['done'])) {
            $query->where('done', filter_var($filters['done'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filters['due_date'])) {
            $query->whereDate('due_date', $filters['due_date']);
        }

        if ($sortBy) {
            $query->orderBy($sortBy, $direction);
        } else {
            $query->latest();
        }

        return $query->get();
    }

    public function store(Project $project, array $data): Task
    {
        return $project->tasks()->create($data);
    }

    public function update(Task $task, array $data): Task
    {
        $task->update($data);

        return $task;
    }

    public function delete(Task $task): void
    {
        $task->delete();
    }
}
