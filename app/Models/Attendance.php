<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'check_in_time', 'check_out_time',
        'latitude', 'longitude', 'late_hour', 'total_working_hour'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
