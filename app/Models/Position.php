<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    protected $guarded = [];
    protected $casts = ['salary' => 'decimal:2', 'vr_per_day' => 'decimal:2'];

    private static ?array $vrCache = null;

    /** Valor de VR/dia por cargo (aba "BASE - VALORES POR FUNÇÃO"). */
    public static function vrFor(?string $jobTitle): float
    {
        if (self::$vrCache === null) {
            self::$vrCache = static::pluck('vr_per_day', 'name')->map(fn ($v) => (float) $v)->all();
        }
        return $jobTitle ? (self::$vrCache[$jobTitle] ?? 0.0) : 0.0;
    }
}
