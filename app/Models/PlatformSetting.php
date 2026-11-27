<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    protected $fillable = [
        'setting_key',
        'setting_group',
        'value',
        'is_public',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'json',
            'is_public' => 'boolean',
        ];
    }
}
