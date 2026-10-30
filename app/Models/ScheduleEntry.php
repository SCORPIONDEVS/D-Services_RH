<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleEntry extends Model
{
    protected $guarded = [];
    protected $casts = ['date' => 'date', 'leader_rotation' => 'boolean'];
}
