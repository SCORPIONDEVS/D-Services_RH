<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    public static function get(string $key, $default = null)
    {
        $row = static::find($key);
        if (!$row) return $default;
        $decoded = json_decode($row->value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $row->value;
    }

    public static function put(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => json_encode($value, JSON_UNESCAPED_UNICODE)]);
    }
}
