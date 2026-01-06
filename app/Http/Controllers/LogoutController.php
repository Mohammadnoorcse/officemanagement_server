<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;


class LogoutController extends Controller
{
//  public function logout(Request $request)
// {
//     $user = $request->user();

//     $attendance = Attendance::where('user_id', $user->id)
//                             ->whereNull('logout_time')
//                             ->latest('date')
//                             ->first();

//     if ($attendance) {
//         $attendance->logout_time = Carbon::now('Asia/Dhaka');
//         $attendance->save();
//     }

//     $user->currentAccessToken()->delete();

//     return response()->json(['message' => 'Logged out successfully']);
// }

public function logout(Request $request)
{
    $user = $request->user();

    Attendance::where('user_id', $user->id)
        ->whereDate('date', now('Asia/Dhaka')->toDateString())
        ->whereNull('logout_time')
        ->update([
            'logout_time' => now('Asia/Dhaka'),
        ]);

    if ($user->currentAccessToken()) {
        $user->currentAccessToken()->delete();
    }

    return response()->json([
        'message' => 'Logged out successfully'
    ]);
}



}
