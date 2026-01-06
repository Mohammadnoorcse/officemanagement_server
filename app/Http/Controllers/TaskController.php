<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{

    public function allTasks()
    {
        // Get all tasks with user info
        $tasks = Task::with('user')->orderBy('created_at', 'desc')->get();

        // Group by status
        $grouped = $tasks->groupBy('status');

        // Ensure all status keys exist
        $result = [
            'do' => $grouped->get('do', collect())->values(),
            'doing' => $grouped->get('doing', collect())->values(),
            'done' => $grouped->get('done', collect())->values(),
        ];

        return response()->json($result);
    }

    public function userTasks($userId)
    {
        $tasks = Task::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($tasks->isEmpty()) {
            return response()->json([
                'message' => 'No tasks found for this user'
            ], 404);
        }

        return response()->json([
            'user_id' => $userId,
            'count' => $tasks->count(),
            'tasks' => $tasks
        ]);
    }

    // Create task (default status = do)
    public function store(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
        ]);

        $task = Task::create([
            'user_id' => $request->user_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'description' => $request->description,
            'status' => 'do',
        ]);

        return response()->json($task, 201);
    }

    // Update status only
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:do,doing,done',
        ]);

        $task = Task::where('id', $id)->first();
        // return $id;


        $task->update([
            'status' => $request->status
        ]);

        return response()->json($task);
    }

    // Delete
    public function destroy(Request $request, $id)
    {
      $task = Task::where('id', $id) ->first();

        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);

        }

        $task->delete();

        return response()->json(['message' => 'Task deleted']);
    }


}
