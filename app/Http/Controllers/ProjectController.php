<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProjectController extends Controller
{
    protected ProjectService $projectService;

    public function __construct(ProjectService $projectService)
    {
        $this->projectService = $projectService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $projects = Project::query()
                ->where('user_id', auth()->id())
                ->when($request->search, function ($query, $search) {
                    $query->where('title', 'like', "%{$search}%");
                })
                ->get();

            return response()->json(['data' => $projects]);
        } catch (\Throwable $e) {
            Log::error('Failed to fetch projects', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Server error'], 500);
        }
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        if ($project->user_id !== auth()->id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->only(['title', 'description']);

        try {
            $project->update($data);

            return response()->json($project);
        } catch (\Throwable $e) {
            Log::error('Failed to update project', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Failed to update project'], 500);
        }
    }

    public function restore(int $id): JsonResponse
    {
        try {
            $project = Project::withTrashed()->find($id);

            if (! $project) {
                return response()->json(['message' => 'Project not found'], 404);
            }

            if ($project->user_id !== auth()->id()) {
                return response()->json(['message' => 'Forbidden'], 403);
            }

            if (! $project->trashed()) {
                return response()->json(['message' => 'Project is not deleted'], 400);
            }

            $restored = $this->projectService->restore($id);

            return response()->json(['message' => 'Project restored successfully']);
        } catch (\Throwable $e) {
            Log::error('Restore failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Failed to restore project'], 500);
        }
    }
}
