<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Lançamento de VT/VR de um colaborador em um período (quinzena "YYYY-MM-1Q"/"2Q" ou mês "YYYY-MM-M").
 * Lançamentos importados do histórico têm os valores originais fixados em vt_amount/vr_amount;
 * quando esses campos são nulos, o valor é calculado: dias × valor/dia atual.
 */
class BenefitEntry extends Model
{
    protected $guarded = [];
    protected $casts = ['extra' => 'array'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function getVtValueAttribute(): float
    {
        if ($this->vt_amount !== null) return (float) $this->vt_amount;
        return round($this->vt_days * $this->employee->vt_per_day, 2);
    }

    /** VR = dias × valor/dia do cargo (o desconto de coparticipação é feito em folha). */
    public function getVrValueAttribute(): float
    {
        if ($this->vr_amount !== null) return (float) $this->vr_amount;
        return round($this->vr_days * $this->employee->vr_per_day, 2);
    }

    /** Valor/dia efetivo (do histórico, quando houver dias; senão o atual do cadastro). */
    public function getVtRateAttribute(): float
    {
        return ($this->vt_amount !== null && $this->vt_days > 0) ? round($this->vt_amount / $this->vt_days, 2) : $this->employee->vt_per_day;
    }

    public function getVrRateAttribute(): float
    {
        return ($this->vr_amount !== null && $this->vr_days > 0) ? round($this->vr_amount / $this->vr_days, 2) : $this->employee->vr_per_day;
    }

    /** Desconto de VR do colaborador (informativo — aplicado na folha). */
    public function getVrDiscountAttribute(): float
    {
        return round($this->vr_days * (float) $this->employee->vr_discount_value, 2);
    }

    public function getTotalAttribute(): float
    {
        return round(
            $this->vt_value + $this->vr_value
            + $this->adjustment_plus - $this->adjustment_minus
            + $this->adjustment_vt + $this->adjustment_vr
            + $this->correction_vt + $this->correction_vr,
            2
        );
    }

    /** "2026-04-1Q" → "Abril/2026 · 1ª quinzena" */
    public static function label(string $period): string
    {
        [$y, $m, $q] = explode('-', $period);
        $month = \Illuminate\Support\Carbon::create((int) $y, (int) $m, 1)->translatedFormat('F/Y');
        return ucfirst($month) . match ($q) { '1Q' => ' · 1ª quinzena', '2Q' => ' · 2ª quinzena', default => ' · mês' };
    }
}
