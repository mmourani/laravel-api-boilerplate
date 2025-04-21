<?php

namespace App\Services;

use App\Contracts\TaskRepositoryInterface;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Collection;

class TaskService
{
    protected TaskRepositoryInterface $repo;

    public function __construct(TaskRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function list(Project $project, array $filters, ?string $sortBy, string $direction): Collection
    {
        return $this->repo->all($project, $filters, $sortBy, $direction);
    }

    public function create(Project $project, array $data): Task
    {
        return $this->repo->store($project, $data);
    }

    public function update(Task $task, array $data): Task
    {
        return $this->repo->update($task, $data);
    }

    public function delete(Task $task): void
    {
        $this->repo->delete($task);
    }
}
