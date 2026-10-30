<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamScale extends Model
{
    protected $table = 'team_scale';
    protected $guarded = [];

    public function scale() { return $this->belongsTo(Scale::class); }
}
