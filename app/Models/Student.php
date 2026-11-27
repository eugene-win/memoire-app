<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'matricule',
        'filiere_id',
        'study_level_id',
        'defense_year_id',
        'enrollment_status',
        'institutionally_verified_at',
        'defended_at',
    ];

    protected function casts(): array
    {
        return [
            'institutionally_verified_at' => 'datetime',
            'defended_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function filiere(): BelongsTo
    {
        return $this->belongsTo(Filiere::class);
    }

    public function studyLevel(): BelongsTo
    {
        return $this->belongsTo(StudyLevel::class);
    }

    public function defenseYear(): BelongsTo
    {
        return $this->belongsTo(DefenseYear::class);
    }
}
