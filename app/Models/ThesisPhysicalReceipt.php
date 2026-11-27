<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThesisPhysicalReceipt extends Model
{
    protected $fillable = [
        'thesis_id',
        'thesis_version_id',
        'responsible_id',
        'received_at',
        'is_compatible',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'is_compatible' => 'boolean',
        ];
    }

    public function thesis(): BelongsTo
    {
        return $this->belongsTo(Thesis::class);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(ThesisVersion::class, 'thesis_version_id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }
}
