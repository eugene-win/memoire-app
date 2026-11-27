<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThesisPublication extends Model
{
    protected $fillable = [
        'thesis_id',
        'thesis_version_id',
        'actor_id',
        'action',
        'visibility',
        'reason',
        'similarity_percent',
        'physical_compatible',
        'rights_confirmed',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'similarity_percent' => 'decimal:2',
            'physical_compatible' => 'boolean',
            'rights_confirmed' => 'boolean',
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

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
