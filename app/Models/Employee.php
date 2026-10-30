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

    /** Período aquisitivo de férias: admissão até +364 dias. */
    public function getAcquisitiveEndAttribute()
    {
        return $this->admission_date?->copy()->addDays(364);
    }

    /** Período de gozo: começa no dia seguinte e dura 365 dias. */
    public function getGrantEndAttribute()
    {
        return $this->acquisitiveEnd?->copy()->addDays(365);
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

    public function getCnpjAttribute(): ?string
    {
        return $this->companyRef?->cnpj;
    }
}
