<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Overtime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OvertimeController extends Controller
{
    /**
     * Start overtime
     */
    public function startOvertime(Request $request)
    {
        $request->validate([
            'attendance_id' => 'required|integer',
            'reason' => 'nullable|string',
        ]);

        $attendance = Attendance::find($request->attendance_id);

        if (!$attendance) {
            return response()->json(['message' => 'Attendance record not found'], 404);
        }

        // Prevent multiple running overtimes
        $runningOvertime = Overtime::where('attendance_id', $attendance->id)
                                ->whereNull('end_time')
                                ->first();
        if ($runningOvertime) {
            return response()->json(['message' => 'Overtime already active'], 403);
        }

        $overtime = Overtime::create([
            'attendance_id' => $attendance->id,
            'start_time' => now(),
            'reason' => $request->reason,
        ]);

        return response()->json([
            'message' => 'Overtime started',
            'overtime' => $overtime
        ]);
    }

    /**
     * End overtime
     */
    public function endOvertime(Request $request)
    {
        $request->validate([
            'overtime_id' => 'required|integer',
        ]);

        $overtime = Overtime::find($request->overtime_id);

        if (!$overtime || !$overtime->start_time) {
            return response()->json(['message' => 'Overtime start not found'], 404);
        }

        $overtime->end_time = now();
        $overtimeMinutes = Carbon::parse($overtime->start_time)
                            ->diffInMinutes(Carbon::parse($overtime->end_time));

        $overtime->overtime_minutes = $overtimeMinutes;
        $overtime->save();

        // Update attendance total overtime
        $attendance = $overtime->attendance;
        $attendance->increment('total_overtime_minutes', $overtimeMinutes);

        return response()->json([
            'message' => 'Overtime ended',
            'overtime_minutes' => $overtimeMinutes,
            'overtime' => $overtime,
            'total_overtime_minutes' => $attendance->total_overtime_minutes
        ]);
    }
}
