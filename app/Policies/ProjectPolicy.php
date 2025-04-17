<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Allow viewing only if the user owns the project.
     */
    public function view(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }

    /**
     * Allow updating only if the user owns the project.
     */
    public function update(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }

    /**
     * Allow deleting only if the user owns the project.
     */
    public function delete(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }
}
