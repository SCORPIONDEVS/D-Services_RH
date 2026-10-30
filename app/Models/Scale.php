<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Scale extends Model
{
    protected $guarded = [];

    public function shiftLinks() { return $this->hasMany(ScaleWorkShift::class); }
    public function teamLinks() { return $this->hasMany(TeamScale::class); }

    /** Sufixo exibido (ex.: "Jornada 12 x 36") pelo nome da escala; consulta única por requisição. */
    public static function labelFor(?string $name): ?string
    {
        static $labels = null;
        $labels ??= static::pluck('label', 'name')->all();
        return $name ? ($labels[$name] ?? null) : null;
    }
}
