<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    protected $guarded = [];
    protected $casts = ['interview_date' => 'date', 'message_sent' => 'boolean'];

    public function jobOpening()
    {
        return $this->belongsTo(JobOpening::class);
    }
}
