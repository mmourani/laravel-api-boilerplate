<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Validator;

class ProjectController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the projects for the current user.
     * Supports search and pagination.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $search = $request->query('search');
        $perPage = $request->query('per_page', 15); // Default to 15 items per page

        $query = Project::where('user_id', $user->id);
        
        // Apply search if provided
        if ($search) {
            $query->search($search);
        }
        
        // Paginate results
        $projects = $query->paginate($perPage);
        
        // Explicitly structure the response with pagination metadata
        return response()->json([
            'data' => $projects->items(),
            'links' => [
                'first' => $projects->url(1),
                'last' => $projects->url($projects->lastPage()),
                'prev' => $projects->previousPageUrl(),
                'next' => $projects->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $projects->currentPage(),
                'from' => $projects->firstItem(),
                'last_page' => $projects->lastPage(),
                'links' => $projects->linkCollection()->toArray(),
                'path' => $projects->path(),
                'per_page' => $projects->perPage(),
                'to' => $projects->lastItem(),
                'total' => $projects->total(),
            ],
        ]);
    }

    /**
     * Store a newly created project in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Create the project
        $project = new Project($request->only(['title', 'description']));
        $project->user_id = $request->user()->id;
        $project->save();

        return response()->json($project, 201);
    }

    /**
     * Display the specified project.
     *
     * @param Request $request
     * @param Project $project
     * @return JsonResponse
     */
    public function show(Request $request, Project $project): JsonResponse
    {
        // Debug authorization information
        $user = $request->user();
        \Log::debug('ProjectController@show authorization debug', [
            'auth_user_id' => $user ? $user->id : null,
            'project_id' => $project->id,
            'project_user_id' => $project->user_id,
            'is_owner' => $user && $user->id === $project->user_id,
            'request_ip' => $request->ip(),
            'is_testing' => app()->environment('testing'),
        ]);

        // Authorization check
        $this->authorize('view', $project);

        return response()->json($project);
    }

    /**
     * Update the specified project in storage.
     *
     * @param Request $request
     * @param Project $project
     * @return JsonResponse
     */
    public function update(Request $request, Project $project): JsonResponse
    {

        // Authorization check
        $this->authorize('update', $project);

        // Validate the request
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Update the project with fields that are present in the request
        if ($request->has('title')) {
            $project->title = $request->title;
        }

        if ($request->has('description')) {
            $project->description = $request->description;
        }

        $project->save();

        return response()->json($project);
    }

    /**
     * Remove the specified project from storage (soft delete).
     *
     * @param Request $request
     * @param Project $project
     * @return JsonResponse
     */
    public function destroy(Request $request, Project $project): JsonResponse
    {

        // Authorization check
        $this->authorize('delete', $project);

        // Soft delete the project (tasks will be cascade soft-deleted via model events)
        $project->delete();

        return response()->json([
            'message' => 'Project deleted successfully'
        ]);
    }

    /**
     * Restore a soft-deleted project.
     *
     * @param Request $request
     * @param int $id Project ID to restore
     * @return JsonResponse
     */
    public function restore(Request $request, int $id): JsonResponse
    {
        try {
            \Log::debug("\n\n=== PROJECT RESTORE COMPREHENSIVE DEBUGGING ===");
            \Log::debug("Project ID to restore: " . $id);

            // Find the project including trashed models
            $project = Project::withTrashed()->find($id);

            if (!$project) {
                \Log::warning("Project not found: ID {$id}");
                return response()->json([
                    'message' => 'Project not found'
                ], 404);
            }

            \Log::debug("Project found", [
                'id' => $project->id,
                'deleted_at' => $project->deleted_at,
                'trashed' => $project->trashed()
            ]);

            // Check if project is already restored
            if (!$project->trashed()) {
                return response()->json([
                    'message' => 'Cannot restore a project that is not soft-deleted',
                    'project_state' => [
                        'id' => $project->id,
                        'deleted_at' => null,
                        'trashed' => false
                    ]
                ], 422);
            }

            // Authorization check
            if ($request->user()->cannot('restore', $project)) {
                return response()->json([
                    'message' => 'Unauthorized to restore this project'
                ], 403);
            }

            // Restore the project
            $project->restore();
            \Log::info("Project restored", ['project_id' => $project->id]);

            return response()->json([
                'message' => 'Project restored successfully',
                'project' => $project->fresh()
            ]);
        } catch (\Exception $e) {
            \Log::error("Error restoring project: " . $e->getMessage());
            \Log::error("Stack trace: " . $e->getTraceAsString());
            return response()->json([
                'message' => 'Error restoring project',
                'error' => 'Unexpected database error'
            ], 500);
        }
    }
}
