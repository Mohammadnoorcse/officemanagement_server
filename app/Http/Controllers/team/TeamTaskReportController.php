<?php

namespace App\Http\Controllers\team;

use App\Http\Controllers\Controller;
use App\Models\TeamTask;
use App\Models\TeamTaskReport;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeamTaskReportController extends Controller
{
    // Employee: submit report
    public function submit(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'task_id' => [
                'required',
                'integer',
                Rule::exists('team_tasks', 'id')->where(function ($q) use ($user) {
                    $q->where('assigned_to', $user->id);
                }),
            ],
            'work_summary' => 'required|string',
            'hours_spent' => 'required|numeric|min:0',
        ]);

        $report = TeamTaskReport::create([
            'task_id' => $request->task_id,
            'user_id' => $user->id,
            'work_summary' => $request->work_summary,
            'hours_spent' => $request->hours_spent,
        ]);

        return response()->json([
            'message' => 'Report submitted successfully',
            'data' => $report
        ]);
    }

    // Team Leader: view team reports
    public function teamReports()
    {
        $user = auth()->user();

        if ($user->role !== 'teamleader') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $reports = TeamTaskReport::with('task', 'user')
            ->whereHas('task', function ($q) use ($user) {
                $q->where('assigned_by', $user->id);
            })
            ->latest()
            ->get();

        return response()->json($reports);
    }
}
