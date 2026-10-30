<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArchiveSheet extends Model
{
    protected $guarded = [];
    protected $casts = ['hidden' => 'boolean', 'columns' => 'array'];

    public function rows()
    {
        return $this->hasMany(ArchiveRow::class);
    }
}
