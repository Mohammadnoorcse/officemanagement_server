<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class HolidayController extends Controller
{
    /**
     * List all holidays
     */
    public function index()
    {
        $holidays = Holiday::orderBy('start_date', 'asc')->get();
        return response()->json(['holidays' => $holidays]);
    }

    /**
     * Add a new holiday and auto-update attendance
     */
public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
        'description' => 'nullable|string',
    ]);

    // Create holiday
    $holiday = Holiday::create([
        'name' => $request->name,
        'start_date' => $request->start_date,
        'end_date' => $request->end_date,
        'description' => $request->description,
    ]);

    $users = User::all();
    $period = CarbonPeriod::create($request->start_date, $request->end_date);
    $updatedAttendances = [];

    foreach ($period as $date) {
        foreach ($users as $user) {
            // updateOrCreate ensures status is set to holiday
            $attendance = Attendance::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'date' => $date->toDateString(),
                ],
                [
                    'status' => 'holiday',
                    'login_time' => null,
                    'logout_time' => null,
                    'total_break_minutes' => 0,
                    'total_overtime_minutes' => 0,
                    
                ]
            );

            $updatedAttendances[] = $attendance;
        }
    }

    return response()->json([
        'message' => 'Holiday added and attendance updated for all users',
        'holiday' => $holiday,
        'attendances' => $updatedAttendances
    ]);
}





    /**
     * Update a holiday
     */
    public function update(Request $request, $id)
    {
        $holiday = Holiday::find($id);
        if (!$holiday) {
            return response()->json(['message' => 'Holiday not found'], 404);
        }

        $request->validate([
            'name' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'description' => 'nullable|string',
        ]);

        $holiday->update([
            'name' => $request->name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'description' => $request->description,
        ]);

        // Update attendance for new holiday period
        $users = User::all();
        $period = CarbonPeriod::create($request->start_date, $request->end_date);

        foreach ($users as $user) {
            foreach ($period as $date) {
                $attendance = Attendance::where('user_id', $user->id)
                    ->where('date', $date->toDateString())
                    ->first();

                if ($attendance) {
                    if (!in_array($attendance->status, ['present', 'late'])) {
                        $attendance->update([
                            'status' => 'holiday',
                            'login_time' => null,
                            'logout_time' => null,
                            'total_break_minutes' => 0,
                            'total_overtime_minutes' => 0,
                            'shift' => null,
                        ]);
                    }
                } else {
                    Attendance::create([
                        'user_id' => $user->id,
                        'date' => $date->toDateString(),
                        'status' => 'holiday',
                        'login_time' => null,
                        'logout_time' => null,
                        'total_break_minutes' => 0,
                        'total_overtime_minutes' => 0,
                        'shift' => null,
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Holiday updated successfully',
            'holiday' => $holiday
        ]);
    }

    /**
     * Delete a holiday
     */
    public function destroy($id)
    {
        $holiday = Holiday::find($id);
        if (!$holiday) {
            return response()->json(['message' => 'Holiday not found'], 404);
        }

        // Remove holiday status from attendance
        $users = User::all();
        $period = CarbonPeriod::create($holiday->start_date, $holiday->end_date);

        foreach ($users as $user) {
            foreach ($period as $date) {
                Attendance::where('user_id', $user->id)
                    ->where('date', $date->toDateString())
                    ->where('status', 'holiday')
                    ->update(['status' => 'absent']); // or 'present' depending on your rules
            }
        }

        $holiday->delete();

        return response()->json(['message' => 'Holiday deleted successfully']);
    }
}
