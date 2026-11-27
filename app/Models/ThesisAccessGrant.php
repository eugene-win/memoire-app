<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThesisAccessGrant extends Model
{
    protected $fillable = [
        'user_id',
        'thesis_id',
        'thesis_payment_id',
        'origin',
        'status',
        'granted_at',
    ];

    protected function casts(): array
    {
        return [
            'granted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function thesis(): BelongsTo
    {
        return $this->belongsTo(Thesis::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(ThesisPayment::class, 'thesis_payment_id');
    }
}
