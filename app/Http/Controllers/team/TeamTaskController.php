<?php

namespace App\Http\Controllers\team;

use App\Http\Controllers\Controller;
use App\Models\TeamTask;
use App\Models\User;
use Illuminate\Http\Request;

class TeamTaskController extends Controller
{
     // Team Leader: view all team tasks
    public function teamTasks()
    {
        $user = auth()->user();
        if($user->role !== 'teamleader'){
            return response()->json(['message'=>'Unauthorized'],403);
        }

        $tasks = TeamTask::with('assignedTo','assignedBy')
            ->where('assigned_by',$user->id)
            ->orderBy('due_date','asc')
            ->get();

        return response()->json($tasks);
    }

    // Assign task to an employee
    public function assign(Request $request)
    {
        $user = auth()->user();
        if($user->role !== 'teamleader'){
            return response()->json(['message'=>'Unauthorized'],403);
        }

        $request->validate([
            'title'=>'required|string',
            'description'=>'nullable|string',
            'assigned_to'=>'required|exists:users,id',
            'due_date'=>'required|date',
        ]);

        $task = TeamTask::create([
            'title'=>$request->title,
            'description'=>$request->description,
            'assigned_by'=>$user->id,
            'assigned_to'=>$request->assigned_to,
            'due_date'=>$request->due_date,
            'status'=>'pending'
        ]);

        return response()->json($task);
    }

    // Employee: view their tasks
    public function myTasks()
    {
        $user = auth()->user();

        $tasks = TeamTask::with('assignedBy')
            ->where('assigned_to',$user->id)
            ->orderBy('due_date','asc')
            ->get();

        return response()->json($tasks);
    }

    // Employee: mark task as completed
    public function updateStatus(Request $request, $taskId)
    {
        $user = auth()->user();
        $task = TeamTask::findOrFail($taskId);

        if($task->assigned_to !== $user->id){
            return response()->json(['message'=>'Unauthorized'],403);
        }

        $request->validate([
            'status'=>'required|in:pending,completed'
        ]);

        $task->status = $request->status;
        $task->save();

        return response()->json($task);
    }
}
