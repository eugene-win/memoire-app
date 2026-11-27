<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentLoginChallenge extends Model
{
    protected $fillable = [
        'student_registry_id',
        'challenge_token_hash',
        'code_hash',
        'email_snapshot_hash',
        'expires_at',
        'attempts',
        'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'attempts' => 'integer',
            'consumed_at' => 'datetime',
        ];
    }

    public function studentRegistry(): BelongsTo
    {
        return $this->belongsTo(StudentRegistry::class);
    }
}
