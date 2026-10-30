<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceBonus extends Model
{
    protected $guarded = [];
    protected $casts = ['competence' => 'date', 'amount' => 'decimal:2'];
}
