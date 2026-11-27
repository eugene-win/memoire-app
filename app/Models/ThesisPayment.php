<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ThesisPayment extends Model
{
    protected $fillable = [
        'user_id',
        'thesis_id',
        'provider',
        'reference',
        'provider_transaction_id',
        'amount',
        'currency',
        'status',
        'provider_payload',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'provider_payload' => 'array',
            'confirmed_at' => 'datetime',
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

    public function accessGrant(): HasOne
    {
        return $this->hasOne(ThesisAccessGrant::class);
    }

    public function webhookReceipts(): HasMany
    {
        return $this->hasMany(PaymentWebhookReceipt::class, 'thesis_payment_id');
    }
}
