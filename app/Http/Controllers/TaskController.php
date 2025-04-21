<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TaskController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', [$project, null]);

        try {
            $query = $project->tasks();

            if ($request->has('priority')) {
                $query->where('priority', $request->priority);
            }

            if ($request->has('done')) {
                $query->where('done', filter_var($request->done, FILTER_VALIDATE_BOOLEAN));
            }

            if ($request->has('due_date')) {
                $query->whereDate('due_date', $request->due_date);
            }

            $direction = $request->get('direction', 'asc');
            $sortBy    = $request->get('sort_by', 'created_at');
            $query->orderBy($sortBy, $direction);

            return response()->json(['data' => $query->get()]);
        } catch (\Throwable $e) {
            Log::error('Failed to list tasks', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Failed to list tasks'], 500);
        }
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', [$project, null]);

        $validated = $request->validate([
            'title'    => 'required|string|max:255',
            'done'     => 'boolean',
            'priority' => 'nullable|in:low,medium,high',
            'due_date' => 'nullable|date',
        ]);

        try {
            $task = $project->tasks()->create($validated);

            return response()->json($task, 201);
        } catch (\Throwable $e) {
            Log::error('Failed to create task', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Failed to create task'], 500);
        }
    }

    public function show(Project $project, Task $task): JsonResponse
    {
        $this->authorize('view', [$project, $task]);

        try {
            return response()->json($task);
        } catch (\Throwable $e) {
            Log::error('Failed to show task', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Failed to show task'], 500);
        }
    }

    public function update(Request $request, Project $project, Task $task): JsonResponse
    {
        $this->authorize('update', [$project, $task]);

        $validated = $request->validate([
            'title'    => 'sometimes|required|string|max:255',
            'done'     => 'boolean',
            'priority' => 'nullable|in:low,medium,high',
            'due_date' => 'nullable|date',
        ]);

        try {
            $task->update($validated);

            return response()->json($task);
        } catch (\Throwable $e) {
            Log::error('Failed to update task', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Failed to update task'], 500);
        }
    }

    public function destroy(Project $project, Task $task): JsonResponse
    {
        $this->authorize('delete', [$project, $task]);

        try {
            $task->delete();

            return response()->json(['message' => 'Task deleted successfully']);
        } catch (\Throwable $e) {
            Log::error('Failed to delete task', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Failed to delete task'], 500);
        }
    }
}
