<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'starts_at',
        'capacity',
        'max_guests_per_registration',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
    ];

    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    public function albumPhotos()
    {
        return $this->hasMany(AlbumPhoto::class);
    }

    public function songRequests()
    {
        return $this->hasMany(SongRequest::class);
    }
}
