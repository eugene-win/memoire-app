<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimilarityReport extends Model
{
    protected $fillable = [
        'thesis_version_id',
        'provider',
        'provider_reference',
        'status',
        'similarity_percent',
        'analysis_result',
        'private_report_path',
        'original_report_filename',
        'references_found',
        'exclusions',
        'analyzed_at',
    ];

    protected function casts(): array
    {
        return [
            'similarity_percent' => 'decimal:2',
            'references_found' => 'array',
            'analyzed_at' => 'datetime',
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(ThesisVersion::class, 'thesis_version_id');
    }
}
