<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobOpening extends Model
{
    protected $guarded = [];
    protected $casts = ['opened_at' => 'date', 'closed_at' => 'date', 'salary' => 'decimal:2'];

    public function candidates()
    {
        return $this->hasMany(Candidate::class);
    }
}
