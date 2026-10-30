<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeOccurrence extends Model
{
    protected $guarded = [];
    protected $casts = ['date' => 'date', 'competence' => 'date'];
}
