<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceController extends Controller
{


    public function currentMonthSummary(Request $request)
    {
        $user = $request->user();

        $now = Carbon::now();

        $attendances = Attendance::where('user_id', $user->id)
            ->whereYear('date', $now->year)
            ->whereMonth('date', $now->month)
            ->get();

        return response()->json([
            'month' => $now->format('Y-m'),
            'counts' => [
                'present' => $attendances->where('status', 'present')->count(),
                'late'    => $attendances->where('status', 'late')->count(),
                'leave'   => $attendances->where('status', 'leave')->count(),
            ],
        ]);
    }




    // today attendace for admin
    public function todaySummary()
    {
        $today = Carbon::today()->toDateString();

        // Fetch today's attendance with user info
        $attendances = Attendance::with('user')
            ->where('date', $today)
            ->get();

        // Counts
        $counts = [
            'present' => $attendances->where('status', 'present')->count(),
            'late'    => $attendances->where('status', 'late')->count(),
            'leave'   => $attendances->where('status', 'leave')->count(),
        ];

        // Employee lists by status
        $employees = [
            'present' => $attendances->where('status', 'present')->values(),
            'late'    => $attendances->where('status', 'late')->values(),
            'leave'   => $attendances->where('status', 'leave')->values(),
        ];

        return response()->json([
            'date' => $today,
            'counts' => $counts,
            'employees' => $employees
        ]);
    }

    // All attendance for current user/admin
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->role === 'admin') {
            $attendances = Attendance::with('user')->orderBy('date', 'desc')->get();
        } else {
            $attendances = Attendance::where('user_id', $user->id)->orderBy('date', 'desc')->get();
        }
        return response()->json($attendances);
    }

    // All attendance for admin
    public function all()
    {
        // Eager load 'user' relation to avoid N+1 problem
        $attendances = Attendance::with('user')
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'count' => $attendances->count(),
            'attendances' => $attendances
        ]);
    }


    // Attendance for one user
    public function userAttendance($userId)
    {
        $user = User::find($userId);
        if (!$user) return response()->json(['message' => 'User not found'], 404);

        $attendances = Attendance::where('user_id', $userId)
            ->orderBy('date', 'desc')->get();

        return response()->json([
            'user' => $user,
            'attendances' => $attendances
        ]);
    }

    // Attendance for one user for specific month
    // public function userAttendanceMonth(Request $request, $userId)
    // {
    //     $request->validate(['month'=>'required|date_format:Y-m']);
    //     [$year,$month] = explode('-',$request->month);

    //     $attendances = Attendance::where('user_id',$userId)
    //         ->whereYear('date',$year)
    //         ->whereMonth('date',$month)
    //         ->orderBy('date','asc')
    //         ->get();

    //     return response()->json($attendances);
    // }


    // Attendance for one user for specific month
    public function userAttendanceMonth(Request $request, $userId)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m'
        ]);

        [$year, $month] = explode('-', $request->month);

        // Fetch attendances with user details
        $attendances = Attendance::with('user')
            ->where('user_id', $userId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date', 'asc')
            ->get();

        // Optional: transform data to include only relevant user fields
        $data = $attendances->map(function ($att) {
            return [
                'id' => $att->id,
                'date' => $att->date,
                'status' => $att->status,
                'login_time' => $att->login_time,
                'logout_time' => $att->logout_time,
                'shift' => $att->shift,
                'ip' => $att->ip,
                'location' => "{$att->city}, {$att->region}, {$att->country}",
                'total_break_minutes' => $att->total_break_minutes,
                'total_overtime_minutes' => $att->total_overtime_minutes,
                'user' => $att->user ? [
                    'id' => $att->user->id,
                    'name' => $att->user->name,
                    'email' => $att->user->email,
                    'image' => $att->user->image
                ] : null,
            ];
        });

        return response()->json($data);
    }


    public function downloadMonthXML($userId, $month)
    {
        [$year, $mon] = explode('-', $month);

        $attendances = Attendance::where('user_id', $userId)
            ->whereYear('date', $year)
            ->whereMonth('date', $mon)
            ->get();

        if ($attendances->isEmpty()) {
            return response()->json(['message' => 'No attendance found for this month'], 404);
        }

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><attendances></attendances>');

        foreach ($attendances as $att) {
            $attNode = $xml->addChild('attendance');
            $attNode->addChild('date', $att->date);
            $attNode->addChild('login_time', $att->login_time ?? '');
            $attNode->addChild('logout_time', $att->logout_time ?? '');
            $attNode->addChild('ip', $att->ip ?? '');
            $attNode->addChild('city', $att->city ?? '');
            $attNode->addChild('country', $att->country ?? '');
        }

        return response($xml->asXML(), 200)
            ->header('Content-Type', 'application/xml')
            ->header('Content-Disposition', 'attachment; filename="attendance_user_' . $userId . '_' . $month . '.xml"');
    }

    public function monthMatrix(Request $request)
    {
        // If month not provided → use current month
        $request->validate([
            'month' => 'nullable|date_format:Y-m'
        ]);

        if ($request->month) {
            [$year, $month] = explode('-', $request->month);
            $dateObj = Carbon::create($year, $month);
        } else {
            $dateObj = Carbon::now();
        }

        $year = $dateObj->year;
        $month = $dateObj->month;

        // All days in month
        $daysInMonth = Carbon::create($year, $month)->daysInMonth;
        $dates = [];

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dates[] = Carbon::create($year, $month, $d)->toDateString();
        }

        // Get users
        $users = User::select('id', 'name')->get();

        // Get attendances
        $attendances = Attendance::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get()
            ->groupBy('user_id');

        $data = [];

        foreach ($users as $user) {

            $userAttendance = $attendances
                ->get($user->id, collect())
                ->keyBy('date');

            $row = [
                'user_id' => $user->id,
                'name' => $user->name,
                'records' => []
            ];

            foreach ($dates as $date) {
                $row['records'][$date] =
                    $userAttendance[$date]->status ?? 'absent';
            }

            $data[] = $row;
        }

        return response()->json([
            'month' => "$year-$month",
            'dates' => $dates,
            'users' => $data
        ]);
    }
}
