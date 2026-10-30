<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArchiveRow extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['cells' => 'array'];
}
