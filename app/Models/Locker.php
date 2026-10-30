<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Locker extends Model
{
    protected $guarded = [];

    public function getOccupiedAttribute(): string
    {
        return $this->occupant ? 'Ocupado' : 'Disponível';
    }
}
