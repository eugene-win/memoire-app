<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentRegistry extends Model
{
    protected $table = 'student_registry';

    protected $fillable = [
        'matricule',
        'email',
        'first_name',
        'last_name',
        'date_of_birth',
        'place_of_birth',
        'filiere_id',
        'study_level_id',
        'class_code',
        'registration_year',
        'academic_status',
        'defense_year_id',
        'defended_at',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'registration_year' => 'integer',
            'defended_at' => 'datetime',
        ];
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
