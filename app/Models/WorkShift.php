<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkShift extends Model
{
    protected $guarded = [];

    public function scaleLinks() { return $this->hasMany(ScaleWorkShift::class); }

    /** Horário no formato usado até hoje: "06:00 às 18:00 - Jornada 12 x 36". */
    public function timeLabel(?string $scaleLabel = null): string
    {
        $t = trim(($this->entry_time ?: '') . ' às ' . ($this->exit_time ?: ''));
        return $scaleLabel ? "$t - $scaleLabel" : $t;
    }
}
