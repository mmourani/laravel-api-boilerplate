<?php

namespace App\Services;

use Throwable;
use App\Models\Project;
use Illuminate\Support\Facades\Log;

class ProjectService
{
    protected Project $project;

    public function __construct(?Project $project = null)
    {
        $this->project = $project ?? new Project();
    }

    public function restore(int $id): ?Project
    {
        try {
            $project = $this->project->withTrashed()->findOrFail($id);
            $project->restore();

            return $project;
        } catch (Throwable $e) {
            Log::error('Failed to restore project', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
