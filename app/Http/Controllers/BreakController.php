<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BreakController extends Controller
{
    /**
     * START BREAK
     */
    public function breakStart(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        // Get today's attendance
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if (!$attendance) {
            return response()->json(['message' => 'You must login before taking break'], 403);
        }

        // Prevent multiple active breaks
        $runningBreak = BreakTime::where('attendance_id', $attendance->id)
            ->whereNull('end_break')
            ->first();

        if ($runningBreak) {
            return response()->json(['message' => 'You already have an active break'], 403);
        }

        // Create new break record
        $break = BreakTime::create([
            'attendance_id' => $attendance->id,
            'reason'        => $request->reason,
            'start_break'   => now(),
        ]);

        return response()->json([
            'message' => 'Break started successfully',
            'break'   => $break
        ]);
    }

    /**
     * END BREAK
     */
    public function breakEnd()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        // Check attendance
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if (!$attendance) {
            return response()->json(['message' => 'You have no attendance today'], 403);
        }

        // Find active break
        $break = BreakTime::where('attendance_id', $attendance->id)
            ->whereNull('end_break')
            ->first();

        if (!$break) {
            return response()->json(['message' => 'No active break found'], 403);
        }

        // Calculate time diff
        $start = Carbon::parse($break->start_break);
        $end   = Carbon::now();
        $minutes = $start->diffInMinutes($end);

        // Update break entry
        $break->update([
            'end_break'     => $end,
            'break_minutes' => $minutes,
        ]);

        // Add to attendance total
        $attendance->increment('total_break_minutes', $minutes);

        return response()->json([
            'message' => 'Break ended successfully',
            'break'   => $break,
            'minutes' => $minutes,
            'total_break_minutes' => $attendance->total_break_minutes,
        ]);
    }
}
