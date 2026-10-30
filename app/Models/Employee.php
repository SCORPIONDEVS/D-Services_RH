<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $guarded = [];

    protected $casts = [
        'admission_date' => 'date', 'aso_admission_date' => 'date', 'termination_date' => 'date',
        'aso_dismissal_date' => 'date', 'rg_issue_date' => 'date', 'birth_date' => 'date',
        'ctps_issue_date' => 'date', 'vacation_start' => 'date', 'vacation_end' => 'date',
        'vt_benefit' => 'boolean', 'vr_benefit' => 'boolean', 'aso_periodic' => 'array',
        'salary' => 'decimal:2', 'vt_tariff' => 'decimal:2', 'vt_trips_per_day' => 'decimal:2',
        'vr_discount_value' => 'decimal:2',
    ];

    public function dependents() { return $this->hasMany(Dependent::class); }
    public function certificates() { return $this->hasMany(MedicalCertificate::class); }
    public function occurrences() { return $this->hasMany(TimeOccurrence::class); }
    public function shiftPatterns() { return $this->hasMany(ShiftPattern::class); }
    public function payrollEntries() { return $this->hasMany(PayrollEntry::class); }
    public function benefitEntries() { return $this->hasMany(BenefitEntry::class); }
    public function companyRef() { return $this->belongsTo(Company::class, 'company', 'short_name'); }
    public function workShift() { return $this->belongsTo(WorkShift::class); }

    /** Horário vindo do vínculo Escala/Jornada ("06:00 às 18:00 - Jornada 12 x 36"); null se ainda não migrado. */
    public function getScheduleLabelAttribute(): ?string
    {
        if (!$this->work_shift_id || !$this->workShift) return null;
        return $this->workShift->timeLabel(Scale::labelFor($this->scale));
    }

    public function scopeActive($q) { return $q->whereNull('termination_date'); }
    public function scopeInactive($q) { return $q->whereNotNull('termination_date'); }

    /* ---- Regras que na planilha eram fórmulas ---- */

    /** Status = ATIVO se não há data de demissão. */
    public function getStatusAttribute(): string
    {
        return $this->termination_date ? 'INATIVO' : 'ATIVO';
    }

    /** Fim do contrato de experiência (45 dias) = admissão + 44. */
    public function getTrial45EndAttribute()
    {
        return $this->admission_date?->copy()->addDays(44);
    }

    /** Fim da prorrogação (90 dias) = fim 45d + 45. */
    public function getTrial90EndAttribute()
    {
        return $this->trial45End?->copy()->addDays(45);
    }

    /** Vencimento do ASO admissional = data do ASO + 364. */
    public function getAsoAdmissionExpiryAttribute()
    {
        return $this->aso_admission_date?->copy()->addDays(364);
    }

    /** Fim do período aquisitivo de férias nº $k+1: 12 meses de calendário a partir da admissão, menos um dia. */
    public function acquisitiveEndFor(int $k = 0): ?\Carbon\CarbonInterface
    {
        return $this->admission_date?->copy()->addYearsNoOverflow($k + 1)->subDay();
    }

    /** Período aquisitivo de férias: admissão até 12 meses depois (menos um dia). */
    public function getAcquisitiveEndAttribute()
    {
        return $this->acquisitiveEndFor(0);
    }

    /** Período de gozo: começa no dia seguinte e dura 12 meses. */
    public function getGrantEndAttribute()
    {
        return $this->acquisitiveEnd?->copy()->addYearNoOverflow();
    }

    /** VT/dia = tarifa × quantidade de passagens por dia (se tem benefício). */
    public function getVtPerDayAttribute(): float
    {
        return $this->vt_benefit ? round((float) $this->vt_tariff * (float) $this->vt_trips_per_day, 2) : 0.0;
    }

    /** VR/dia vem da tabela de valores por cargo. */
    public function getVrPerDayAttribute(): float
    {
        return $this->vr_benefit ? Position::vrFor($this->job_title) : 0.0;
    }

    /** Direito ao prêmio-assiduidade/VA: salário abaixo do teto salarial configurado. */
    public function getVaAssiduityEligibleAttribute(): bool
    {
        $cap = (float) Setting::get('assiduity_salary_cap', 0);
        return $cap > 0 && (float) $this->salary < $cap;
    }

    /**
     * Situação de férias para destacar na lista de funcionários (somente ATIVOS):
     *  - 'em_ferias': hoje está entre o início e o fim das férias lançadas;
     *  - 'vencendo' : há um período aquisitivo completo sem férias lançadas e o prazo de gozo termina em até $alertDays dias;
     *  - 'vencida'  : o prazo de gozo desse período já passou.
     * Períodos aquisitivos se repetem a cada ano. Como só a última férias fica lançada, uma férias com início depois do
     * fim de um período conta como gozada para ele e para os anteriores. Retorna null quando não há nada a destacar.
     *
     * @return array{status:string, date:\Carbon\CarbonInterface, days:int}|null  date = fim das férias ou prazo de gozo
     */
    public function vacationStatus(?\Carbon\CarbonInterface $today = null, int $alertDays = 90): ?array
    {
        if ($this->termination_date || !$this->admission_date) return null;
        $today = ($today ?? today())->copy()->startOfDay();

        if ($this->vacation_start && $this->vacation_end
            && $this->vacation_start->copy()->startOfDay()->lte($today) && $this->vacation_end->copy()->startOfDay()->gte($today)) {
            return ['status' => 'em_ferias', 'date' => $this->vacation_end, 'days' => (int) $today->diffInDays($this->vacation_end->copy()->startOfDay())];
        }

        for ($k = 0; $k < 60; $k++) {
            $end = $this->acquisitiveEndFor($k)->startOfDay();
            if ($end->gt($today)) return null;                                   // próximo período ainda não completou
            if ($this->vacation_start && $end->lt($this->vacation_start->copy()->startOfDay())) continue; // já gozado/agendado

            $deadline = $end->copy()->addYearNoOverflow();                       // mesma regra de grantEnd
            $days = (int) round($today->diffInDays($deadline, false));
            if ($days < 0) return ['status' => 'vencida', 'date' => $deadline, 'days' => $days];
            return $days <= $alertDays ? ['status' => 'vencendo', 'date' => $deadline, 'days' => $days] : null;
        }

        return null;
    }

    public function getCnpjAttribute(): ?string
    {
        return $this->companyRef?->cnpj;
    }
}
