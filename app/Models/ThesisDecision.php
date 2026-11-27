<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThesisDecision extends Model
{
    protected $fillable = [
        'thesis_id',
        'thesis_version_id',
        'actor_id',
        'type',
        'source',
        'reason',
    ];

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
