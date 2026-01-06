<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shift;
use App\Models\User;

class ShiftController extends Controller
{
    // Show all shifts
    public function index()
    {
        $shifts = Shift::with('user')->orderBy('id', 'desc')->get();
        return response()->json($shifts);
    }

    // Create shift
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'name'    => 'required|string|max:255', // corrected
            'date'    => 'required|date',           // added date validation
            'start_time' => 'required',
            'end_time'   => 'required',
        ]);

        $shift = Shift::create($request->all());

        return response()->json([
            'message' => 'Shift created successfully',
            'data' => $shift
        ]);
    }

    // Update shift
    public function update(Request $request, $id)
    {
        $shift = Shift::findOrFail($id);

        $request->validate([
            'name'    => 'required|string|max:255', // corrected
            'date'    => 'required|date',           // added date
            'start_time' => 'required',
            'end_time'   => 'required',
        ]);

        $shift->update($request->all());

        return response()->json([
            'message' => 'Shift updated',
            'data' => $shift
        ]);
    }

    // Delete shift
    public function destroy($id)
    {
        $shift = Shift::findOrFail($id);
        $shift->delete();

        return response()->json(['message' => 'Shift deleted']);
    }
}
