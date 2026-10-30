<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScaleWorkShift extends Model
{
    protected $table = 'scale_work_shift';
    protected $guarded = [];

    public function scale() { return $this->belongsTo(Scale::class); }
    public function workShift() { return $this->belongsTo(WorkShift::class); }
}
