<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Salary;
use Carbon\Carbon;

class SalaryController extends Controller
{
    // 1️⃣ Calculate salary for a single user
    public function calculate(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'month' => 'required|date_format:Y-m',
        ]);

        return $this->calculateSalaryForUser($request->user_id, $request->month);
    }

    // 2️⃣ Calculate salary for all active users (returns result without saving)
    public function calculateAllActive(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);

        $users = User::where('status','active')->get();
        $result = [];

        foreach ($users as $user) {
            $result[] = $this->calculateSalaryForUser($user->id, $request->month, false);
        }

        return response()->json([
            'month' => $request->month,
            'salaries' => $result
        ]);
    }

    // 3️⃣ Update salary status for a user/month
    public function updateStatusByMonth(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'month' => 'required|date_format:Y-m',
            'status' => 'required|in:pending,paid',
        ]);

        $salary = Salary::where('user_id',$request->user_id)
            ->where('month',$request->month)
            ->first();

        if(!$salary) return response()->json(['message'=>'Salary record not found'],404);

        $salary->update(['status'=>$request->status]);

        return response()->json(['message'=>'Salary status updated','salary'=>$salary]);
    }

    // 4️⃣ Fetch salary for a user/month
    public function getSalary(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'month' => 'required|date_format:Y-m',
        ]);

        $salary = Salary::where('user_id',$request->user_id)
            ->where('month',$request->month)
            ->first();

        if(!$salary) return response()->json(['message'=>'Salary not found'],404);

        return response()->json(['salary'=>$salary]);
    }

    // 5️⃣ Fetch all active users salaries for a month (calculates & saves if not exist)
    public function getAllActiveSalary(Request $request)
    {
        $month = $request->query('month') ?? Carbon::now()->format('Y-m');

        $users = User::where('status', 'active')->get();
        $result = [];

        foreach ($users as $user) {
            $salary = $this->calculateSalaryForUser($user->id, $month, true);
            $result[] = [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'salary' => $salary
            ];
        }

        return response()->json([
            'month' => $month,
            'active_users' => $result
        ]);
    }

    // =========================
    // Internal function to calculate salary
    private function calculateSalaryForUser($userId, $month, $save = true)
    {
        $user = User::find($userId);
        $carbonMonth = Carbon::parse($month);

        $year = $carbonMonth->year;
        $m = $carbonMonth->month;
        $daysInMonth = $carbonMonth->daysInMonth;

        // Fetch attendances excluding holidays
        $attendances = Attendance::where('user_id', $userId)
            ->whereYear('date', $year)
            ->whereMonth('date', $m)
            ->get();

        // Count statuses
        $presentDays = $attendances->where('status','present')->count();
        $lateDays = $attendances->where('status','late')->count();
        $leaveDays = $attendances->where('status','leave')->count();
        $absentDays = $attendances->where('status','absent')->count();
        $holidayDays = $attendances->where('status','holiday')->count();
        $weekendDays = $attendances->where('status','weekend')->count(); // if you mark weekends

        $lateDeductionDays = intdiv($lateDays,3);
        $deductionDays = $lateDeductionDays + $absentDays;

        $basicSalary = $user->salary ?? 0;
        $perDaySalary = $daysInMonth>0 ? $basicSalary / $daysInMonth : 0;
        $deductionAmount = $perDaySalary * $deductionDays;

        $totalOvertimeMinutes = $attendances->sum('total_overtime_minutes');
        $overtimeRatePerMinute = $perDaySalary / (8*60);
        $overtimeAmount = $totalOvertimeMinutes * $overtimeRatePerMinute;
        $workingDay = $presentDays + ($lateDays - $lateDeductionDays) + $leaveDays + $holidayDays + $weekendDays;

        $finalSalary = ($workingDay * $perDaySalary) + $overtimeAmount;

        if ($save) {
            $salary = Salary::firstOrNew(['user_id'=>$userId,'month'=>$month]);

            $salary->basic_salary = $basicSalary;
            $salary->month_in_days = $daysInMonth;
            $salary->total_working_days = $workingDay;
            $salary->present_days = $presentDays;
            $salary->late_days = $lateDays;
            $salary->leave_days = $leaveDays;
            $salary->absent_days = $absentDays;
            $salary->holiday = $holidayDays;
            $salary->weekend_days = $weekendDays;
            $salary->late_deduction_days = $lateDeductionDays;
            $salary->deduction_amount = $deductionAmount;
            $salary->total_overtime_minutes = $totalOvertimeMinutes;
            $salary->overtime_amount = $overtimeAmount;
            $salary->per_day_amount = $perDaySalary;
            $salary->final_salary = $finalSalary;

            if (!$salary->exists) $salary->status = 'pending';

            $salary->save();
            return $salary;
        }

        return [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'basic_salary' => $basicSalary,
            'month_in_days' => $daysInMonth,
            'total_working_days' => $workingDay,
            'present_days' => $presentDays,
            'late_days' => $lateDays,
            'leave_days' => $leaveDays,
            'absent_days' => $absentDays,
            'holiday' => $holidayDays,
            'weekend_days' => $weekendDays,
            'late_deduction_days' => $lateDeductionDays,
            'deduction_amount' => $deductionAmount,
            'total_overtime_minutes' => $totalOvertimeMinutes,
            'overtime_amount' => $overtimeAmount,
            'per_day_amount' => $perDaySalary,
            'final_salary' => $finalSalary,
            'status' => 'pending'
        ];
    }
}
