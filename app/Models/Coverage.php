<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coverage extends Model
{
    protected $guarded = [];
    protected $casts = ['date' => 'date', 'pay_date' => 'date', 'amount' => 'decimal:2'];
}
