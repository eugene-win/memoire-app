<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThesisConsultation extends Model
{
    protected $fillable = [
        'user_id',
        'thesis_id',
        'thesis_version_id',
        'access_type',
        'consulted_at',
    ];

    protected function casts(): array
    {
        return [
            'consulted_at' => 'datetime',
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

    public function version(): BelongsTo
    {
        return $this->belongsTo(ThesisVersion::class, 'thesis_version_id');
    }
}
