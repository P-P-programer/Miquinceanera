<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistrationPerson extends Model
{
    use HasFactory;

    protected $table = 'registration_people';

    protected $fillable = [
        'registration_id',
        'name',
        'email',
        'is_titular',
    ];

    protected $casts = [
        'is_titular' => 'boolean',
    ];

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }
}
