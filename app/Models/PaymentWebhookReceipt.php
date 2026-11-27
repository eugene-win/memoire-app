<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentWebhookReceipt extends Model
{
    protected $fillable = [
        'provider',
        'provider_event_id',
        'thesis_payment_id',
        'payload_hash',
        'processing_status',
        'failure_reason',
        'received_at',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(ThesisPayment::class, 'thesis_payment_id');
    }
}
