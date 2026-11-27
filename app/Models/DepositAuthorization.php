<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepositAuthorization extends Model
{
    protected $fillable = [
        'student_id',
        'authorized_by_id',
        'status',
        'reason',
        'authorized_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'authorized_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function authorizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by_id');
    }
}
