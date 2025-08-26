<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Schedule extends Model
{
    use HasFactory;

    protected $table = 'schedule';

    protected $fillable = [
        'provider_id',
        'day',
        'start_at',
        'end_at',
        'hours_per_session',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }
}
