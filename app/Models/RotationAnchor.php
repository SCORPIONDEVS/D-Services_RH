<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RotationAnchor extends Model
{
    protected $guarded = [];

    protected $casts = ['work_date' => 'date'];

    public function employee() { return $this->belongsTo(Employee::class); }
}
