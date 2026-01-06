<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveController extends Controller
{
    /**
     * Apply for leave
     */
    public function applyLeave(Request $request)
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'type' => 'required|string',
            'reason' => 'nullable|string',
        ]);

        $user = Auth::user();

        $leave = Leave::create([
            'user_id' => $user->id,
            'from_date' => $request->from_date,
            'to_date' => $request->to_date,
            'type' => $request->type,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Leave applied successfully',
            'leave' => $leave
        ]);
    }

    /**
     * Approve or reject leave (admin)
     */
    public function updateLeaveStatus(Request $request, $leaveId)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $leave = Leave::find($leaveId);
        if (!$leave) {
            return response()->json(['message' => 'Leave not found'], 404);
        }

        $leave->status = $request->status;
        $leave->save();

        // If approved, mark attendance as 'leave'
        if ($request->status === 'approved') {
            $from = Carbon::parse($leave->from_date);
            $to = Carbon::parse($leave->to_date);

            while ($from->lte($to)) {
                $attendance = Attendance::firstOrCreate(
                    ['user_id' => $leave->user_id, 'date' => $from->toDateString()],
                    ['status' => 'leave']
                );

                // If attendance exists, update status
                if ($attendance->exists && $attendance->status != 'leave') {
                    $attendance->status = 'leave';
                    $attendance->save();
                }

                $from->addDay();
            }
        }

        return response()->json([
            'message' => 'Leave status updated',
            'leave' => $leave
        ]);
    }

    /**
     * List all leaves for the logged-in user
     */
    public function myLeaves()
    {
        $user = Auth::user();
        $leaves = Leave::where('user_id', $user->id)->orderBy('from_date', 'desc')->get();

        return response()->json(['leaves' => $leaves]);
    }

    /**
     * Admin: List all leaves
     */
    public function allLeaves()
    {
        $leaves = Leave::with('user')->orderBy('from_date', 'desc')->get();
        return response()->json(['leaves' => $leaves]);
    }
}
