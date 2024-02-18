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
        // Get the IP address of the user
        $userIp = $request->ip();

        // Get the location data based on the IP address
        $locationData = Location::get($userIp);

        // Get the authenticated employee instance
        $employee = auth()->user();

        if (!$employee) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Retrieve the employee's shift
        $shift = $employee->shift->first();

        if (!$shift) {
            return response()->json(['message' => 'Employee shift not found'], 404);
        }

        // Get the current time in the employee's timezone
        $currentTime = Carbon::now($employee->timezone);

        // Set the expected check-in time based on the shift
        $shiftStart = Carbon::createFromFormat('H:i:s', $shift->start_time, $employee->timezone);
        $expectedCheckinTime = $shiftStart;

        // Calculate late hours if the check-in time is after the expected check-in time
        if ($currentTime->greaterThan($expectedCheckinTime)) {
            $lateMinutes = $currentTime->diffInMinutes($expectedCheckinTime);
            $lateHours = $lateMinutes / 60;
        } else {
            $lateHours = 0; // No late hours if checked in on time or early
        }

        // Create a new attendance record
        $attendance = new Attendance();

        // Assign latitude and longitude to the attendance record
        $attendance->latitude = $locationData->latitude;
        $attendance->longitude = $locationData->longitude;

        // Set the check-in time
        $attendance->check_in_time = $currentTime;

        // Assign the employee ID
        $attendance->employee_id = $employee->id;

        // Assign the late hours
        $attendance->late_hour = $lateHours;

        // Save the attendance record
        $attendance->save();

        // Optionally, you can return a response to indicate success or failure
        return response()->json(['message' => 'Check-in recorded successfully']);
    }

    public function checkout(Request $request)
    {
        // Get the IP address of the user
        $userIp = $request->ip();

        // Get the location data based on the IP address
        $locationData = Location::get($userIp);

        // Get the authenticated employee instance
        $employee = auth()->user();

        if (!$employee) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Get the current time in the employee's timezone
        $currentTime = Carbon::now($employee->timezone);

        // Create a new attendance record for checkout
        $attendance = new Attendance();

        // Assign latitude and longitude to the attendance record
        $attendance->latitude = $locationData->latitude;
        $attendance->longitude = $locationData->longitude;

        // Set the check-out time
        $attendance->check_out_time = $currentTime;

        // Assign the employee ID
        $attendance->employee_id = $employee->id;

        // Save the attendance record
        $attendance->save();

        $this->calculateTotalWorkingHours($employee->id, $currentTime->toDateString());

        // Optionally, you can return a response to indicate success or failure
        return response()->json(['message' => 'Check-out recorded successfully']);
    }

    public function calculateTotalWorkingHours($employeeId, $date)
    {
        // Parse the date
        $date = Carbon::parse($date);

        // Get the start and end of the day
        $startOfDay = $date->startOfDay();
        $endOfDay = $date->endOfDay();

        // Retrieve attendance records for the employee for the specified date
        $attendances = Attendance::where('employee_id', $employeeId)
            ->whereBetween('check_in_time', [$startOfDay, $endOfDay])
            ->get();

        $totalWorkingHours = 0;

        // Calculate working hours for each attendance record
        foreach ($attendances as $attendance) {
            // If there's a check-out time, calculate the working hours
            if ($attendance->check_out_time) {
                $checkIn = Carbon::parse($attendance->check_in_time);
                $checkOut = Carbon::parse($attendance->check_out_time);
                $workingHours = $checkOut->diffInHours($checkIn);
                $totalWorkingHours += $workingHours;
            } else {
                // If there's no check-out time, assume a default check-out time
                // and calculate the working hours until the end of the day
                $checkIn = Carbon::parse($attendance->check_in_time);
                $workingHours = $endOfDay->diffInHours($checkIn);
                $totalWorkingHours += $workingHours;
            }
        }

        // Update the total_working_hour field in the attendance record
        // Assuming there is only one attendance record for the specified date
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
            // Fetch attendance information for the admin
            $attendanceSummary = Attendance::with(['employee', 'shift'])
                ->orderBy('check_in_time', 'desc')
                ->get(['employee_id', 'check_in_time', 'check_out_time', 'total_working_hour', 'late_hour', 'latitude', 'longitude']);

            // Optionally, you can customize the response format here if needed
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
        // Retrieve notifications for the authenticated admin
        $notifications = $request->user()->notifications;

        // Transform notifications as needed
        $transformedNotifications = $notifications->map(function ($notification) {
            return [
                'id' => $notification->id,
                'type' => $notification->type,
                'data' => $notification->data,
                'created_at' => $notification->created_at,
            ];
        });

        // Return JSON response with notifications
        return response()->json([
            'notifications' => $transformedNotifications,
        ]);
    }
}
