<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'titular_name',
        'titular_email',
        'titular_phone',
        'total_people',
        'qr_code',
        'status',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function people()
    {
        return $this->hasMany(RegistrationPerson::class);
    }

    public function attendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class);
    }
}
