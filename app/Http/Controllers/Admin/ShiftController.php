<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;

class ShiftController extends Controller
{
    // Show all shifts
    // public function index()
    // {
    //     $shifts = Shift::with('user')->orderBy('id', 'desc')->get();
    //     return response()->json($shifts);
    // }

    // Create shift
    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'user_id' => 'required|exists:users,id',
    //         'name'    => 'required|string|max:255', // corrected
    //         'date'    => 'required|date',           // added date validation
    //         'start_time' => 'required',
    //         'end_time'   => 'required',
    //     ]);

    //     $shift = Shift::create($request->all());

    //     return response()->json([
    //         'message' => 'Shift created successfully',
    //         'data' => $shift
    //     ]);
    // }

    // Update shift
    // public function update(Request $request, $id)
    // {
    //     $shift = Shift::findOrFail($id);

    //     $request->validate([
    //         'name'    => 'required|string|max:255', // corrected
    //         'date'    => 'required|date',           // added date
    //         'start_time' => 'required',
    //         'end_time'   => 'required',
    //     ]);

    //     $shift->update($request->all());

    //     return response()->json([
    //         'message' => 'Shift updated',
    //         'data' => $shift
    //     ]);
    // }

    // // Delete shift
    // public function destroy($id)
    // {
    //     $shift = Shift::findOrFail($id);
    //     $shift->delete();

    //     return response()->json(['message' => 'Shift deleted']);
    // }


    public function index()
    {
        $shifts = Shift::with('user')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($shift) {
                $shift->day_of_week =
                    ucfirst(strtolower($shift->day_of_week));
                return $shift;
            });

        return response()->json($shifts);
    }


   

public function store(Request $request)
{
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'shifts' => 'required|array|min:1',
        'shifts.*.day_of_week' => 'required|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
        'shifts.*.start_time' => 'required',
        'shifts.*.end_time' => 'required',
    ]);

    foreach ($request->shifts as $shift) {
        Shift::updateOrCreate(
            [
                'user_id' => $request->user_id,
                'day_of_week' => $shift['day_of_week'],
            ],
            [
                'name' => ucfirst($shift['day_of_week']).' Shift',
                'start_time' => Carbon::createFromFormat('H:i', $shift['start_time'], 'Asia/Dhaka')->format('H:i'),
                'end_time' => Carbon::createFromFormat('H:i', $shift['end_time'], 'Asia/Dhaka')->format('H:i'),
            ]
        );
    }

    return response()->json(['message' => 'Weekly shifts saved']);
}

public function update(Request $request, $id)
{
    $shift = Shift::findOrFail($id);

    $request->validate([
        'name' => 'required|string|max:255',
        'day_of_week' => 'nullable|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
        'date' => 'nullable|date',
        'start_time' => 'required',
        'end_time' => 'required',
    ]);

    // Prevent using both date & day_of_week
    if ($request->date && $request->day_of_week) {
        return response()->json([
            'message' => 'Use either date OR day_of_week, not both'
        ], 422);
    }

    // Prevent overlapping shifts
    $conflict = Shift::where('user_id', $shift->user_id)
        ->where('id', '!=', $shift->id)
        ->where(function ($q) use ($request) {
            if ($request->date) {
                $q->whereDate('date', $request->date);
            } else {
                $q->where('day_of_week', $request->day_of_week);
            }
        })
        ->where(function ($q) use ($request) {
            $q->whereBetween('start_time', [$request->start_time, $request->end_time])
              ->orWhereBetween('end_time', [$request->start_time, $request->end_time]);
        })
        ->exists();

    if ($conflict) {
        return response()->json([
            'message' => 'Shift time overlaps with another shift'
        ], 409);
    }

    $shift->update([
        'name' => $request->name,
        'day_of_week' => $request->day_of_week,
        'date' => $request->date ? Carbon::parse($request->date, 'Asia/Dhaka')->toDateString() : null,
       'start_time' => substr($request->start_time, 0, 5), // "01:10:00" → "01:10"
    'end_time' => substr($request->end_time, 0, 5),
    ]);

    return response()->json([
        'message' => 'Shift updated successfully',
        'data' => $shift
    ]);
}


    public function destroy($id)
    {
        $shift = Shift::findOrFail($id);

        // ❌ Prevent delete if attendance exists
        if ($shift->attendances()->exists()) {
            return response()->json([
                'message' => 'Cannot delete shift with attendance records'
            ], 409);
        }

        $shift->delete();

        return response()->json([
            'message' => 'Shift deleted successfully'
        ]);
    }



}
