<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'matricule',
        'role',
        'deposit_authorized',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'deposit_authorized' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function thesisPayments(): HasMany
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

    public function thesisArchiveImports(): HasMany
    {
        return $this->hasMany(ThesisArchiveImport::class, 'responsible_id');
    }

    public function depositAuthorizationEvents(): HasMany
    {
        return $this->hasMany(DepositAuthorizationEvent::class, 'student_id');
    }

    public function depositAuthorizationActions(): HasMany
    {
        return $this->hasMany(DepositAuthorizationEvent::class, 'responsible_id');
    }
}
