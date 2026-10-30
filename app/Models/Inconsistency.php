<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inconsistency extends Model
{
    protected $guarded = [];
    protected $casts = ['date' => 'date', 'competence' => 'date'];
}
