<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class ProjectService
{
    public function restore($id)
    {
        try {
            $project = Project::withTrashed()->findOrFail($id);
            
            if (auth()->user()->cannot('restore', $project)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
    
            if (!$project->trashed()) {
                return response()->json(['message' => 'Project is not deleted'], 400);
            }
    
            $project->restore();
    
            return response()->json(['message' => 'Project restored successfully']);
    
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Project not found'], 404);
    
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error("Database error during project restoration: " . $e->getMessage());
            return response()->json(['message' => 'Error restoring project'], 500);
        }
    }
}
