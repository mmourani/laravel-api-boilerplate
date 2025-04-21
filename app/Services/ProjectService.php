<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProjectService
{
    protected Project $model;

    public function __construct(Project $model = null)
    {
        $this->model = $model ?? new Project();
    }

    /**
     * Attempt to restore a soft-deleted project.
     *
     * @param  int  $projectId
     * @return Project|null
     */
    public function restore(int $projectId): ?Project
    {
        try {
            // Include soft-deleted records
            $project = $this->model->withTrashed()->findOrFail($projectId);

            // ❌ Reject if not soft-deleted
            if (!$project->trashed()) {
                return null;
            }

            // ❌ Reject if user is not the owner
            if ($project->user_id !== Auth::id()) {
                return null;
            }

            $project->restore();

            return $project;
        } catch (\Throwable $e) {
            Log::error('Failed to restore project', [
                'project_id' => $projectId,
                'error'      => $e->getMessage(),
                'user_id'    => Auth::id(),
            ]);

            return null;
        }
    }
}