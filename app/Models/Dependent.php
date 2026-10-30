<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dependent extends Model
{
    protected $guarded = [];
    protected $table = 'employee_dependents';
    protected $casts = ['birth_date' => 'date'];
}
