<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'registration_id',
        'registration_person_id',
        'scanned_by',
        'scanned_at',
        'note',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
    ];

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }

    public function person()
    {
        return $this->belongsTo(RegistrationPerson::class, 'registration_person_id');
    }
}
