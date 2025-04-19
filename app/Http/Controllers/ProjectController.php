<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class ProjectController extends Controller
{
    use AuthorizesRequests;

    protected ProjectService $service;

    public function __construct(ProjectService $service)
    {
        $this->service = $service;
    }

    /**
     * List the authenticated user's projects.
     */
    public function index(Request $request)
    {
        $query = $request->user()->projects()->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        if (is_numeric($perPage)) {
            return JsonResource::collection($query->paginate($perPage));
        }

        return JsonResource::collection($query->get());
    }

    /**
     * Store a new project.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $project = $request->user()->projects()->create($validated);

        return response()->json($project, 201);
    }

    /**
     * Show a specific project.
     */
    public function show(Project $project)
    {
        $this->authorize('view', $project);

        return response()->json($project);
    }

    /**
     * Update an existing project.
     */
    public function update(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $project->update($request->only(['title', 'description']));

        return response()->json($project);
    }

    /**
     * Delete a project.
     */
    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);

        $project->delete();

        return response()->json(['message' => 'Project deleted successfully']);
    }

    /**
     * Restore a soft-deleted project.
     */
    public function restore($id)
    {
        try {
            $project = Project::withTrashed()->findOrFail($id);

            // Check if user is unauthorized
            if (auth()->user()->cannot('restore', $project)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Already active project
            if (! $project->trashed()) {
                return response()->json(['message' => 'Project is not deleted'], 400);
            }

            // Try restoring
            $project->restore();

            return response()->json(['message' => 'Project restored successfully']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Project not found'], 404);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error during project restoration: '.$e->getMessage());

            return response()->json(['message' => 'Error restoring project'], 500);
        }
    }
}
