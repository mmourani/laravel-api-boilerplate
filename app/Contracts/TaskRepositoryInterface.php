<?php

namespace App\Contracts;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Collection;

/**
 * Interface TaskRepositoryInterface
 */
interface TaskRepositoryInterface
{
    /**
     * @param Project $project
     * @param array<string, mixed> $filters
     * @param string|null $sortBy
     * @param string $direction
     * @return Collection<int, Task>
     */
    public function all(Project $project, array $filters = [], ?string $sortBy = null, string $direction = 'asc'): Collection;

    /**
     * @param Project $project
     * @param array<string, mixed> $data
     * @return Task
     */
    public function store(Project $project, array $data): Task;

    /**
     * @param Task $task
     * @param array<string, mixed> $data
     * @return Task
     */
    public function update(Task $task, array $data): Task;

    /**
     * @param Task $task
     * @return void
     */
    public function delete(Task $task): void;
}
