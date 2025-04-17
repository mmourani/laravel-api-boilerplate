<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ProjectController extends Controller
{
    use AuthorizesRequests;
    /**
     * List the authenticated user's projects.
     */
    public function index(Request $request)
    {
        return $request->user()->projects()->latest()->get();
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
     * Show a specific project (only if owned by the user).
     */
    public function show(Project $project)
    {
        $this->authorize('view', $project);

        return response()->json($project);
    }

    /**
     * Update an existing project (only if owned by the user).
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
     * Delete a project (only if owned by the user).
     */
    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);

        $project->delete();

        return response()->json(['message' => 'Project deleted successfully']);
    }
}
