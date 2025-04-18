<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     * 
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        // Users can view a list of their own projects
        return true;
    }

    /**
     * Determine whether the user can view the model.
     * 
     * @param User $user
     * @param Project $project
     * @return bool
     */
    public function view(User $user, Project $project): bool
    {
        $authorized = $user->id === $project->user_id;
        
        \Log::debug("ProjectPolicy@view authorization check", [
            'user_id' => $user->id,
            'project_id' => $project->id,
            'project_user_id' => $project->user_id,
            'authorized' => $authorized
        ]);
        
        return $authorized;
    }

    /**
     * Determine whether the user can create models.
     * 
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        // All authenticated users can create projects
        return true;
    }

    /**
     * Determine whether the user can update the model.
     * 
     * @param User $user
     * @param Project $project
     * @return bool
     */
    public function update(User $user, Project $project): bool
    {
        $authorized = $user->id === $project->user_id;
        
        \Log::debug("ProjectPolicy@update authorization check", [
            'user_id' => $user->id,
            'project_id' => $project->id,
            'project_user_id' => $project->user_id,
            'authorized' => $authorized
        ]);
        
        return $authorized;
    }

    /**
     * Determine whether the user can delete the model.
     * 
     * @param User $user
     * @param Project $project
     * @return bool
     */
    public function delete(User $user, Project $project): bool
    {
        $authorized = $user->id === $project->user_id;
        
        \Log::debug("ProjectPolicy@delete authorization check", [
            'user_id' => $user->id,
            'project_id' => $project->id,
            'project_user_id' => $project->user_id,
            'authorized' => $authorized
        ]);
        
        return $authorized;
    }

    /**
     * Determine whether the user can restore the model.
     * 
     * @param User $user
     * @param Project $project
     * @return bool
     */
    public function restore(User $user, Project $project): bool
    {
        $authorized = $user->id === $project->user_id;
        
        \Log::debug("ProjectPolicy@restore authorization check", [
            'user_id' => $user->id,
            'project_id' => $project->id,
            'project_user_id' => $project->user_id,
            'authorized' => $authorized,
            'trashed' => $project->trashed()
        ]);
        
        return $authorized;
    }

    /**
     * Determine whether the user can permanently delete the model.
     * 
     * @param User $user
     * @param Project $project
     * @return bool
     */
    public function forceDelete(User $user, Project $project): bool
    {
        $authorized = $user->id === $project->user_id;
        
        \Log::debug("ProjectPolicy@forceDelete authorization check", [
            'user_id' => $user->id,
            'project_id' => $project->id,
            'project_user_id' => $project->user_id,
            'authorized' => $authorized
        ]);
        
        return $authorized;
    }
}
