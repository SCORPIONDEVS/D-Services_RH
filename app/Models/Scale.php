<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Scale extends Model
{
    protected $guarded = [];

    /** Rótulos por nome da escala, lidos uma vez por processo e descartados ao salvar/excluir uma escala. */
    private static ?array $labels = null;

    protected static function booted(): void
    {
        static::saved(fn () => self::flushLabels());
        static::deleted(fn () => self::flushLabels());
    }

    public static function flushLabels(): void
    {
        self::$labels = null;
    }

    public function shiftLinks() { return $this->hasMany(ScaleWorkShift::class); }
    public function teamLinks() { return $this->hasMany(TeamScale::class); }

    /** Sufixo exibido (ex.: "Jornada 12 x 36") pelo nome da escala. */
    public static function labelFor(?string $name): ?string
    {
        self::$labels ??= static::pluck('label', 'name')->all();
        return $name ? (self::$labels[$name] ?? null) : null;
    }
}
