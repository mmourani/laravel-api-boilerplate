<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the task.
     * 
     * @param User $user
     * @param Task $task
     * @return bool
     */
    public function view(User $user, Task $task): bool
    {
        // If task has no project_id, deny access
        if (!$task->project_id) {
            \Log::debug("TaskPolicy@view: Denied (orphaned task)", [
                'user_id' => $user->id,
                'task_id' => $task->id,
                'project_id' => null,
            ]);
            return false;
        }

        // Load project (including soft-deleted ones) to check ownership
        $project = Project::withTrashed()->find($task->project_id);
        
        // If project doesn't exist (rare case), deny access
        if (!$project) {
            \Log::debug("TaskPolicy@view: Denied (project not found)", [
                'user_id' => $user->id,
                'task_id' => $task->id,
                'project_id' => $task->project_id,
            ]);
            return false;
        }

        $authorized = $user->id === $project->user_id;
        
        \Log::debug("TaskPolicy@view authorization check", [
            'user_id' => $user->id,
            'task_id' => $task->id,
            'project_id' => $project->id,
            'project_user_id' => $project->user_id,
            'project_trashed' => $project->trashed(),
            'authorized' => $authorized
        ]);
        
        return $authorized;
    }

    /**
     * Determine whether the user can create a task for a project.
     * 
     * @param User $user
     * @param int $projectId
     * @return bool
     */
    public function create(User $user, int $projectId): bool
    {
        // If no project ID provided, deny permission
        if (!$projectId) {
            \Log::debug("TaskPolicy@create: Denied (no project_id)", [
                'user_id' => $user->id,
            ]);
            return false;
        }

        // Check if project exists and user owns it
        $project = Project::find($projectId);
        
        // If project doesn't exist, deny permission
        if (!$project) {
            \Log::debug("TaskPolicy@create: Denied (project not found)", [
                'user_id' => $user->id,
                'project_id' => $projectId,
            ]);
            return false;
        }

        $authorized = $user->id === $project->user_id;
        
        \Log::debug("TaskPolicy@create authorization check", [
            'user_id' => $user->id,
            'project_id' => $project->id,
            'project_user_id' => $project->user_id,
            'authorized' => $authorized
        ]);
        
        return $authorized;
    }

    /**
     * Determine whether the user can update the task.
     * 
     * @param User $user
     * @param Task $task
     * @return bool
     */
    public function update(User $user, Task $task): bool
    {
        // If task has no project_id, deny access
        if (!$task->project_id) {
            \Log::debug("TaskPolicy@update: Denied (orphaned task)", [
                'user_id' => $user->id,
                'task_id' => $task->id,
                'project_id' => null,
            ]);
            return false;
        }

        // Load project (including soft-deleted ones) to check ownership
        $project = Project::withTrashed()->find($task->project_id);
        
        // If project doesn't exist (rare case), deny access
        if (!$project) {
            \Log::debug("TaskPolicy@update: Denied (project not found)", [
                'user_id' => $user->id,
                'task_id' => $task->id,
                'project_id' => $task->project_id,
            ]);
            return false;
        }

        $authorized = $user->id === $project->user_id;
        
        \Log::debug("TaskPolicy@update authorization check", [
            'user_id' => $user->id,
            'task_id' => $task->id,
            'project_id' => $project->id,
            'project_user_id' => $project->user_id,
            'authorized' => $authorized
        ]);
        
        return $authorized;
    }

    /**
     * Determine whether the user can delete the task.
     * 
     * @param User $user
     * @param Task $task
     * @return bool
     */
    public function delete(User $user, Task $task): bool
    {
        // If task has no project_id, deny access
        if (!$task->project_id) {
            \Log::debug("TaskPolicy@delete: Denied (orphaned task)", [
                'user_id' => $user->id,
                'task_id' => $task->id,
                'project_id' => null,
            ]);
            return false;
        }

        // Load project (including soft-deleted ones) to check ownership
        $project = Project::withTrashed()->find($task->project_id);
        
        // If project doesn't exist (rare case), deny access
        if (!$project) {
            \Log::debug("TaskPolicy@delete: Denied (project not found)", [
                'user_id' => $user->id,
                'task_id' => $task->id,
                'project_id' => $task->project_id,
            ]);
            return false;
        }

        $authorized = $user->id === $project->user_id;
        
        \Log::debug("TaskPolicy@delete authorization check", [
            'user_id' => $user->id,
            'task_id' => $task->id,
            'project_id' => $project->id,
            'project_user_id' => $project->user_id,
            'authorized' => $authorized
        ]);
        
        return $authorized;
    }

    /**
     * Determine whether the user can restore the task.
     * 
     * @param User $user
     * @param Task $task
     * @return bool
     */
    public function restore(User $user, Task $task): bool
    {
        // If task has no project_id, deny access
        if (!$task->project_id) {
            \Log::debug("TaskPolicy@restore: Denied (orphaned task)", [
                'user_id' => $user->id,
                'task_id' => $task->id,
                'project_id' => null,
            ]);
            return false;
        }

        // Load project (including soft-deleted ones) to check ownership
        $project = Project::withTrashed()->find($task->project_id);
        
        // If project doesn't exist (rare case), deny access
        if (!$project) {
            \Log::debug("TaskPolicy@restore: Denied (project not found)", [
                'user_id' => $user->id,
                'task_id' => $task->id,
                'project_id' => $task->project_id,
            ]);
            return false;
        }

        $authorized = $user->id === $project->user_id;
        
        \Log::debug("TaskPolicy@restore authorization check", [
            'user_id' => $user->id,
            'task_id' => $task->id,
            'project_id' => $project->id,
            'project_user_id' => $project->user_id,
            'project_trashed' => $project->trashed(),
            'task_trashed' => $task->trashed(),
            'authorized' => $authorized
        ]);
        
        return $authorized;
    }

    /**
     * Determine whether the user can permanently delete the task.
     * 
     * @param User $user
     * @param Task $task
     * @return bool
     */
    public function forceDelete(User $user, Task $task): bool
    {
        // If task has no project_id, deny access
        if (!$task->project_id) {
            \Log::debug("TaskPolicy@forceDelete: Denied (orphaned task)", [
                'user_id' => $user->id,
                'task_id' => $task->id,
                'project_id' => null,
            ]);
            return false;
        }

        // Load project (including soft-deleted ones) to check ownership
        $project = Project::withTrashed()->find($task->project_id);
        
        // If project doesn't exist (rare case), deny access
        if (!$project) {
            \Log::debug("TaskPolicy@forceDelete: Denied (project not found)", [
                'user_id' => $user->id,
                'task_id' => $task->id,
                'project_id' => $task->project_id,
            ]);
            return false;
        }

        $authorized = $user->id === $project->user_id;
        
        \Log::debug("TaskPolicy@forceDelete authorization check", [
            'user_id' => $user->id,
            'task_id' => $task->id,
            'project_id' => $project->id,
            'project_user_id' => $project->user_id,
            'authorized' => $authorized
        ]);
        
        return $authorized;
    }
}

