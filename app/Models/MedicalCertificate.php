<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalCertificate extends Model
{
    protected $guarded = [];
    protected $casts = ['start_date' => 'date', 'competence' => 'date'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /** Término = data + dias - 1 (mesma fórmula da planilha). */
    public function getEndDateAttribute()
    {
        return $this->start_date && $this->days ? $this->start_date->copy()->addDays($this->days - 1) : null;
    }
}
