<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Thesis extends Model
{
    protected $fillable = [
        'reference',
        'student_id',
        'title',
        'author_name',
        'matricule',
        'department',
        'filiere_id',
        'defended_year',
        'defense_year_id',
        'supervisor',
        'abstract',
        'keywords',
        'status',
        'visibility',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'defended_year' => 'integer',
            'keywords' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function filiere(): BelongsTo
    {
        return $this->belongsTo(Filiere::class);
    }

    public function defenseYear(): BelongsTo
    {
        return $this->belongsTo(DefenseYear::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ThesisVersion::class);
    }

    public function currentVersion(): HasOne
    {
        return $this->hasOne(ThesisVersion::class)->latestOfMany('version_number');
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(ThesisDecision::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ThesisPayment::class);
    }

    public function accessGrants(): HasMany
    {
        return $this->hasMany(ThesisAccessGrant::class);
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(ThesisConsultation::class);
    }

    public function archiveImport(): HasOne
    {
        return $this->hasOne(ThesisArchiveImport::class);
    }

    public function physicalReceipts(): HasMany
    {
        return $this->hasMany(ThesisPhysicalReceipt::class);
    }

    public function publications(): HasMany
    {
        return $this->hasMany(ThesisPublication::class);
    }
}
