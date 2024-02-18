<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stevebauman\Location\Facades\Location;
//use app\Http\Middleware\TrackEmployeeCheckins;

class AttendanceController extends Controller
{
    /* public function __construct()
    {
        $this->middleware('TrackEmployeeCheckins')->except('summary');
    } */

    public function checkin(Request $request)
    {
        $userIp = $request->ip();

        $locationData = Location::get($userIp);

        $employee = auth()->user();

        if (!$employee) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $shift = $employee->shift->first();

        if (!$shift) {
            return response()->json(['message' => 'Employee shift not found'], 404);
        }

        $currentTime = Carbon::now($employee->timezone);

        $shiftStart = Carbon::createFromFormat('H:i:s', $shift->start_time, $employee->timezone);
        $expectedCheckinTime = $shiftStart;

        if ($currentTime->greaterThan($expectedCheckinTime)) {
            $lateMinutes = $currentTime->diffInMinutes($expectedCheckinTime);
            $lateHours = $lateMinutes / 60;
        } else {
            $lateHours = 0;
        }

        $attendance = new Attendance();

        $attendance->latitude = $locationData->latitude;
        $attendance->longitude = $locationData->longitude;

        $attendance->check_in_time = $currentTime;


        $attendance->employee_id = $employee->id;


        $attendance->late_hour = $lateHours;


        $attendance->save();


        return response()->json(['message' => 'Check-in recorded successfully']);
    }

    public function checkout(Request $request)
    {

        $userIp = $request->ip();


        $locationData = Location::get($userIp);


        $employee = auth()->user();

        if (!$employee) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }


        $currentTime = Carbon::now($employee->timezone);


        $attendance = new Attendance();


        $attendance->latitude = $locationData->latitude;
        $attendance->longitude = $locationData->longitude;


        $attendance->check_out_time = $currentTime;


        $attendance->employee_id = $employee->id;


        $attendance->save();

        $this->calculateTotalWorkingHours($employee->id, $currentTime->toDateString());

        return response()->json(['message' => 'Check-out recorded successfully']);
    }

    public function calculateTotalWorkingHours($employeeId, $date)
    {
        $date = Carbon::parse($date);

        $startOfDay = $date->startOfDay();
        $endOfDay = $date->endOfDay();

        $attendances = Attendance::where('employee_id', $employeeId)
            ->whereBetween('check_in_time', [$startOfDay, $endOfDay])
            ->get();

        $totalWorkingHours = 0;

        foreach ($attendances as $attendance) {
            if ($attendance->check_out_time) {
                $checkIn = Carbon::parse($attendance->check_in_time);
                $checkOut = Carbon::parse($attendance->check_out_time);
                $workingHours = $checkOut->diffInHours($checkIn);
                $totalWorkingHours += $workingHours;
            } else {

                $checkIn = Carbon::parse($attendance->check_in_time);
                $workingHours = $endOfDay->diffInHours($checkIn);
                $totalWorkingHours += $workingHours;
            }
        }


        $attendanceRecord = Attendance::where('employee_id', $employeeId)
            ->whereDate('check_in_time', $date)
            ->first();

        if ($attendanceRecord) {
            $attendanceRecord->total_working_hour = $totalWorkingHours;
            $attendanceRecord->save();
        }

        return $totalWorkingHours;
    }

    public function summary()
    {
        try {
            $attendanceSummary = Attendance::with(['employee', 'shift'])
                ->orderBy('check_in_time', 'desc')
                ->get(['employee_id', 'check_in_time', 'check_out_time', 'total_working_hour', 'late_hour', 'latitude', 'longitude']);

            return response()->json([
                'code' => 200,
                'message' => 'Attendance summary retrieved successfully.',
                'data' => ['attendance_summary' => $attendanceSummary]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => 'Failed to retrieve attendance summary.',
                'data' => []
            ]);
        }
    }

    public function notifications(Request $request)
    {
        $notifications = $request->user()->notifications;

        $transformedNotifications = $notifications->map(function ($notification) {
            return [
                'id' => $notification->id,
                'type' => $notification->type,
                'data' => $notification->data,
                'created_at' => $notification->created_at,
            ];
        });

        return response()->json([
            'notifications' => $transformedNotifications,
        ]);
    }
}
