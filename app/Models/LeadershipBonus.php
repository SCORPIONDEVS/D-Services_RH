<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadershipBonus extends Model
{
    protected $guarded = [];
    protected $casts = ['competence' => 'date'];
}
