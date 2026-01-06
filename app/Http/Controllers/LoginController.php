<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\GeoLocation;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Stevebauman\Location\Facades\Location;
use Symfony\Component\Mime\Address;

class LoginController extends Controller
{
    // public function login(Request $request)
    // {
    //     $request->validate([
    //         'email'    => 'required|email',
    //         'password' => 'required',
    //     ]);

    //     if (!Auth::attempt($request->only('email', 'password'))) {
    //         return response()->json(['message' => 'Invalid credentials'], 401);
    //     }

    //     $user = Auth::user();

    //     // Generate token
    //     $token = $user->createToken('auth_token')->plainTextToken;

    //     if($user->role != 'admin'){
    //           // Get IP + Location
    //         $ip = $request->ip();
    //         // $ip = "45.112.56.1";
    //         $location = Location::get($ip);

    //         // Save attendance
    //         Attendance::create([
    //             'user_id'     => $user->id,
    //             'date'        => Carbon::today()->toDateString(),
    //             'login_time'  => now(),
    //             'ip'          => $ip,
    //             'country'     => $location->countryName ?? null,
    //             'region'      => $location->regionName ?? null,
    //             'city'        => $location->cityName ?? null,
    //             'zip'         => $location->zipCode ?? null,
    //             'latitude'    => $request->latitude ?? null,
    //             'longitude'   => $request->longitude ?? null,
    //             'timezone'    => $location->timezone ?? null,
    //             'isp'         => $location->isp ?? null,
    //             'organization'=> $location->organization ?? null,
    //             'as'          => $location->as ?? null,
    //         ]);

    //     }

      
    //     return response()->json([
    //         'access_token' => $token,
    //         'token_type'   => 'Bearer',
    //         'user'         => [
    //         'id'    => $user->id,
    //         'name'  => $user->name,
    //         'email' => $user->email,
    //         'role'  => $user->role, 
    //         'image' => $user->image,
    //     ],
    //     ]);
    // }

public function login(Request $request)
{
    $request->validate([
        'email'    => 'required|email',
        'password' => 'required',
    ]);

    // 1. Check credentials
    if (!Auth::attempt($request->only('email', 'password'))) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    $user = Auth::user();

    // 2. Block inactive users
    if ($user->status === 'inactive') {
        Auth::logout();
        return response()->json(['message' => 'Your account is inactive. Please contact admin.'], 403);
    }

    // 3. Generate auth token
    $token = $user->createToken('auth_token')->plainTextToken;

    // 4. Admin bypass: no attendance required
    if ($user->role === 'admin') {
        return $this->loginSuccess($token, $user);
    }

    // 5. Current time (Bangladesh)
    $currentTime = Carbon::now('Asia/Dhaka');
    $today = $currentTime->toDateString(); // "YYYY-MM-DD"

    // 6. Get today's shift
    $shift = Shift::where('user_id', $user->id)->whereDate('date', '<=', $today)->first();

    if (!$shift) {
        return response()->json(['message' => 'You have no shift assigned for today'], 403);
        // return $today;
    }

    // 7. Shift start and end times
    $shiftStart = Carbon::createFromFormat('Y-m-d H:i:s', $today . ' ' . $shift->start_time, 'Asia/Dhaka');
    $shiftEnd   = Carbon::createFromFormat('Y-m-d H:i:s', $today . ' ' . $shift->end_time, 'Asia/Dhaka');

    // 8. Overnight shift (end < start)
    if ($shiftEnd->lt($shiftStart)) {
        $shiftEnd->addDay();
    }

    // 9. Check if within shift time
    // if ($currentTime->lt($shiftStart)) {
    //     return response()->json(['message' => 'Your shift has not started yet.',$currentTime,$shiftStart], 403);
    // }

    if ($currentTime->gt($shiftEnd)) {
        return response()->json(['message' => 'Your shift time is over. You cannot login now.'], 403);
    }

    // 10. Attendance status (10 min grace)
    $status = $currentTime->gt($shiftStart->copy()->addMinutes(10)) ? 'late' : 'present';

    // 11. Prevent duplicate attendance
    $existing = Attendance::where('user_id', $user->id)
        ->where('date', $today)
        ->first();

    if ($existing) {
        return response()->json(['message' => 'You have already logged in for this shift today.'], 403);
    }

    // 12. Get IP + Location
    $ip = $request->ip();
    $location = Location::get($ip);

    // 13. Save attendance
    Attendance::create([
        'user_id'      => $user->id,
        'shift_id'     => $shift->id,
        'date'         => $today,
        'login_time'   => $currentTime,
        'ip'           => $ip,
        'country'      => $location->countryName ?? null,
        'region'       => $location->regionName ?? null,
        'city'         => $location->cityName ?? null,
        'zip'          => $location->zipCode ?? null,
        'latitude'     => $request->latitude ?? null,
        'longitude'    => $request->longitude ?? null,
        'timezone'     => $location->timezone ?? null,
        'isp'          => $location->isp ?? null,
        'organization' => $location->organization ?? null,
        'as'           => $location->as ?? null,
        'status'       => $status,
    ]);

    // 14. Update GeoLocation if provided
    if ($request->latitude && $request->longitude) {
        GeoLocation::updateOrCreate(
            ['user_id' => $user->id],
            [
                'latitude'    => $request->latitude,
                'longitude'   => $request->longitude,
                'device_info' => $request->device_info ?? null,
            ]
        );
    }

    // 15. Return success
    return $this->loginSuccess($token, $user);
}

    /* ------------------------------------------------------
     * RESPONSE HELPER
     * ------------------------------------------------------ */
    private function loginSuccess($token, $user)
    {
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
                'image' => $user->image,
            ],
        ]);
    }


  public function getLocationFromIp($userId)
    {
        // Get latest attendance of the user
        $attendance = Attendance::where('user_id', $userId)
            ->orderBy('id', 'DESC')
            ->first();

        if (!$attendance) {
            return response()->json(['message' => 'No attendance found'], 404);
        }

        // Get stored IP from attendance
        $ip = $attendance->ip;

        // Get updated location using that IP
        $location = Location::get($ip);

        return response()->json([
            'ip'          => $ip,
            'country'     => $location->countryName ?? null,
            'region'      => $location->regionName ?? null,
            'city'        => $location->cityName ?? null,
            'zip'         => $location->zipCode ?? null,
            'latitude'    => $location->latitude ?? null,
            'longitude'   => $location->longitude ?? null,
            'timezone'    => $location->timezone ?? null,
            'isp'         => $location->isp ?? null,
            'organization'=> $location->organization ?? null,
            'as'          => $location->as ?? null,
        ]);
    }

}
