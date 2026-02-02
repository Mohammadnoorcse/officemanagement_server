<?php

use App\Http\Controllers\Admin\ShiftController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceWeekendController;
use App\Http\Controllers\BreakController;
use App\Http\Controllers\GeoLocationController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\OvertimeController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\team\GroupChatController;
use App\Http\Controllers\team\GroupController;
use App\Http\Controllers\team\TeamTaskController;
use App\Http\Controllers\team\TeamTaskReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



Route::post('/login',[LoginController::class,'login']);
Route::get('/location',[LoginController::class,'getLocationFromIp']);
Route::middleware('auth:sanctum')->post('/logout',[LogoutController::class,'logout']);
Route::middleware('auth:sanctum')->get('/attendances',[AttendanceController::class,'index']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/break/start', [BreakController::class, 'breakStart']);
    Route::post('/break/end', [BreakController::class, 'breakEnd']);


});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/geo-locations', [GeoLocationController::class, 'store']);
    Route::get('/geo-location/{userId}', [GeoLocationController::class, 'getUserLocationById']);
     Route::get('/user-locations', [GeoLocationController::class, 'index']);


});

Route::get('/reverse-geocode', [GeoLocationController::class, 'reverseGeocode']);




Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/register', [RegisterController::class, 'register']);
    Route::get('/shifts', [ShiftController::class, 'index']);
    Route::post('/shifts', [ShiftController::class, 'store']);
    Route::put('/shifts/{id}', [ShiftController::class, 'update']);
    Route::delete('/shifts/{id}', [ShiftController::class, 'destroy']);

    Route::get('/users', [UserManagementController::class, 'index']);
    Route::put('/users/{id}/role', [UserManagementController::class, 'updateRole']);
    Route::delete('/users/{id}', [UserManagementController::class, 'destroy']);


    // All attendances
    Route::get('/attendances/all', [AttendanceController::class, 'all']); // admin only
    Route::get('/attendances/{userId}', [AttendanceController::class, 'userAttendance']);
    Route::get('/attendances/{userId}/month', [AttendanceController::class, 'userAttendanceMonth']);
    Route::get('/attendances/{userId}/download/{month}', [AttendanceController::class, 'downloadMonthXML']);
    Route::get('/attendance/today-summary', [AttendanceController::class, 'todaySummary']);
    Route::get('/month-matrix', [AttendanceController::class, 'monthMatrix']);


});

Route::get('/user-location/{id}', [LoginController::class, 'getLocationFromIp']);

Route::middleware('auth:sanctum')->group(function(){
    Route::get('/attendances/{userId}/month', [AttendanceController::class, 'userAttendanceMonth']);
    Route::post('/salary/calculate',[SalaryController::class,'calculate']); // single user
    Route::post('/salary/calculate-active',[SalaryController::class,'calculateAllActive']); // all active users
    Route::post('/salary/update-status',[SalaryController::class,'updateStatusByMonth']); // mark paid/pending
    Route::get('/salary',[SalaryController::class,'getSalary']); // fetch salary for user/month
    Route::get('/salary/all',[SalaryController::class,'getAllActiveSalary']);

});


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/holidays', [HolidayController::class, 'index']);
    Route::post('/holidays', [HolidayController::class, 'store']);
    Route::put('/holidays/{id}', [HolidayController::class, 'update']);
    Route::delete('/holidays/{id}', [HolidayController::class, 'destroy']);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/leave/apply', [LeaveController::class, 'applyLeave']);
    Route::get('/leave/my', [LeaveController::class, 'myLeaves']);
    Route::get('/leave/all', [LeaveController::class, 'allLeaves']); // admin
    Route::put('/leave/{leaveId}/status', [LeaveController::class, 'updateLeaveStatus']); // admin
});


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/overtime/start', [OvertimeController::class, 'startOvertime']);
    Route::post('/overtime/end', [OvertimeController::class, 'endOvertime']);
});
Route::middleware('auth:sanctum')->group(function () {
    // WEEKEND CRUD
    Route::post('/weekend/create', [AttendanceWeekendController::class, 'createWeekend']);
    Route::get('/weekend/{user_id}', [AttendanceWeekendController::class, 'getWeekends']);
    Route::put('/weekend/update/{id}', [AttendanceWeekendController::class, 'updateWeekend']);
    Route::delete('/weekend/delete/{id}', [AttendanceWeekendController::class, 'deleteWeekend']);
    Route::get('/users-weekend-list', [AttendanceWeekendController::class, 'allUsersWeekendList']);

    // ATTENDANCE AUTO GENERATION
    Route::post('/attendance/generate', [AttendanceWeekendController::class, 'generateAttendance']);
    Route::post('/attendance/generate-today', [AttendanceWeekendController::class, 'generateTodayAttendance']);
});


Route::middleware('auth:sanctum')->group(function () {

    Route::post('/tasks', [TaskController::class, 'store']);
    Route::put('/tasks/{taskId}/status', [TaskController::class, 'updateStatus']);
    Route::delete('/tasks/{taskId}', [TaskController::class, 'destroy']);
    Route::get('/users/{userId}/tasks', [TaskController::class, 'userTasks']);

    Route::get('/tasks/all', [TaskController::class, 'allTasks']);

    // attendance count for user
    Route::get('/attendance/current-month-summary/user', [AttendanceController::class, 'currentMonthSummary']);
});


// team
Route::middleware('auth:sanctum')->group(function(){
     Route::get('/users', [UserManagementController::class, 'index']);

    Route::get('/team-tasks',[TeamTaskController::class,'teamTasks']);
    Route::post('/team-task/assign',[TeamTaskController::class,'assign']);
    Route::get('/group/members', [GroupController::class, 'myGroupMembers']);

    Route::get('/my-tasks',[TeamTaskController::class,'myTasks']);
    Route::post('/my-task/{id}/status',[TeamTaskController::class,'updateStatus']);
    Route::post('/team-report/submit',[TeamTaskReportController::class,'submit']);
    Route::get('/team-reports',[TeamTaskReportController::class,'teamReports']);
    Route::get('/group-chat/{id}/messages',[GroupChatController::class,'messages']);
    Route::post('/group-chat/{id}/message',[GroupChatController::class,'send']);
    Route::post('/group/create',[GroupController::class,'create']);
    Route::get('/group/my-groups',[GroupController::class,'myGroups']);
    Route::post('/group/{groupId}/add-member',[GroupController::class,'addMember']);
    Route::post('/group/{groupId}/remove-member', [GroupController::class, 'removeMember']);

    Route::delete('/group/{groupId}', [GroupController::class, 'delete']);



});






