<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ThesisVersion extends Model
{
    protected $fillable = [
        'version_number',
        'file_path',
        'original_filename',
        'file_size',
        'checksum',
        'metadata_snapshot',
        'rights_confirmed_at',
        'rights_statement_snapshot',
        'submitted_by_id',
        'submitted_at',
        'mime_type',
        'version_status',
        'analysis_status',
        'analysis_provider',
        'analysis_reference',
        'similarity_percent',
        'analysis_exclusions',
        'analysis_report_path',
        'analysis_report_filename',
        'analysis_note',
        'analyzed_at',
        'physical_received_at',
        'physical_received_by',
    ];

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'file_size' => 'integer',
            'metadata_snapshot' => 'array',
            'rights_confirmed_at' => 'datetime',
            'submitted_at' => 'datetime',
            'similarity_percent' => 'decimal:2',
            'analyzed_at' => 'datetime',
            'physical_received_at' => 'datetime',
        ];
    }

    public function thesis(): BelongsTo
    {
        return $this->belongsTo(Thesis::class);
    }

    public function physicalReceiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'physical_received_by');
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(ThesisConsultation::class, 'thesis_version_id');
    }

    public function similarityReports(): HasMany
    {
        return $this->hasMany(SimilarityReport::class, 'thesis_version_id');
    }

    public function physicalReceipts(): HasMany
    {
        return $this->hasMany(ThesisPhysicalReceipt::class, 'thesis_version_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_id');
    }
}
