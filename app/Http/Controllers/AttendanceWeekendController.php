<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\UserWeekend;
use App\Models\Attendance;
use App\Models\User;

class AttendanceWeekendController extends Controller
{

    public function allUsersWeekendList()
    {
       $users = User::with(['weekends:id,user_id,day'])
        ->whereHas('weekends') // Only users with at least one weekend
        ->select('id', 'name')
        ->orderBy('id', 'desc')
        ->get();

    return response()->json($users);
    }
    // ---------------------------
    // CREATE WEEKEND DAY
    // ---------------------------
    public function createWeekend(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'day' => 'required|string'
        ]);

        $weekend = UserWeekend::create([
            'user_id' => $request->user_id,
            'day' => strtolower($request->day)
        ]);

        return response()->json(['message' => 'Weekend day created', 'data' => $weekend]);
    }

    // ---------------------------
    // GET WEEKEND DAYS FOR A USER
    // ---------------------------
    public function getWeekends($user_id)
    {
        $weekends = UserWeekend::where('user_id', $user_id)->get();
        return response()->json($weekends);
    }

    // ---------------------------
    // UPDATE WEEKEND DAY
    // ---------------------------
    public function updateWeekend(Request $request, $id)
    {
        $request->validate(['day' => 'required|string']);

        $weekend = UserWeekend::findOrFail($id);
        $weekend->day = strtolower($request->day);
        $weekend->save();

        return response()->json(['message' => 'Weekend day updated', 'data' => $weekend]);
    }

    // ---------------------------
    // DELETE WEEKEND DAY
    // ---------------------------
    public function deleteWeekend($id)
    {
        $weekend = UserWeekend::findOrFail($id);
        $weekend->delete();

        return response()->json(['message' => 'Weekend day deleted']);
    }

    // ---------------------------
    // AUTO GENERATE ATTENDANCE FOR FULL MONTH
    // ---------------------------
    public function generateAttendance()
    {
        $year = now()->year;
        $month = now()->month;

        $users = User::all();

        $start = Carbon::create($year, $month, 1);
        $end = $start->copy()->endOfMonth();

        foreach ($users as $user) {
            $date = $start->copy();
            while ($date <= $end) {
                $this->autoAttendanceForUser($user, $date);
                $date->addDay();
            }
        }

        return response()->json([
            'message' => "Attendance generated for {$start->format('F Y')}"
        ]);
    }

    // ---------------------------
    // DAILY AUTOMATION → TODAY ONLY
    // ---------------------------
  public function generateTodayAttendance()
{
    $today = now();
    $users = User::all();

    foreach ($users as $user) {
        $this->autoAttendanceForUser($user, $today);
    }

    // ✅ Return today's attendance rows
    $attendance = Attendance::where('date', $today->toDateString())
        ->with('user:id,name')
        ->get();

    return response()->json([
        'message' => "Attendance generated for today: " . $today->toDateString(),
        'data' => $attendance
    ]);
}


    // ---------------------------
    // HELPER: AUTO ATTENDANCE BASED ON WEEKEND
    // ---------------------------
 private function autoAttendanceForUser($user, $date)
{
    // Get weekend days for this user
    $weekendDays = UserWeekend::where('user_id', $user->id)
        ->pluck('day')
        ->map(fn ($d) => trim(strtolower($d)))
        ->toArray();

    // If user has no weekend data, do nothing
    if (empty($weekendDays)) {
        return; // leave attendance as-is
    }

    // Determine if today is a weekend
    $dayName = strtolower($date->format('l'));
    $status = in_array($dayName, $weekendDays) ? 'weekend' : null;

    // If today is not weekend, leave status unchanged
    if (!$status) {
        return;
    }

    // Check if attendance already exists
    $existing = Attendance::where([
        'user_id' => $user->id,
        'date' => $date->toDateString()
    ])->first();

    // Do not overwrite existing holiday record
    if ($existing && $existing->status === 'holiday') {
        return;
    }

    // Update or create attendance only for weekend users
    Attendance::updateOrCreate(
        [
            'user_id' => $user->id,
            'date' => $date->toDateString(),
        ],
        [
            'status' => $status,
            'total_break_minutes' => 0,
            'total_overtime_minutes' => 0,
            'shift' => null,
        ]
    );
}




}
