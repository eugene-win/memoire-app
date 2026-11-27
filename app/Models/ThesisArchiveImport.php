<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThesisArchiveImport extends Model
{
    protected $fillable = [
        'thesis_id',
        'responsible_id',
        'source',
        'physical_checked',
        'diffusion_rights_confirmed',
        'control_result',
        'imported_at',
    ];

    protected function casts(): array
    {
        return [
            'physical_checked' => 'boolean',
            'diffusion_rights_confirmed' => 'boolean',
            'imported_at' => 'datetime',
        ];
    }

    public function thesis(): BelongsTo
    {
        return $this->belongsTo(Thesis::class);
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }
}
