<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $attributes = [
        'account_status' => 'active',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'matricule',
        'role',
        'requested_role',
        'account_status',
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

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function studentRegistry(): HasOne
    {
        return $this->hasOne(StudentRegistry::class);
    }

    public function depositAuthorization(): HasOne
    {
        return $this->hasOne(DepositAuthorization::class, 'student_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)
            ->withPivot('assigned_by_id')
            ->withTimestamps();
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function roleAssignmentEvents(): HasMany
    {
        return $this->hasMany(RoleAssignmentEvent::class);
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

    public function hasRole(string|array $roles): bool
    {
        $roles = (array) $roles;
        if (in_array($this->role, $roles, true)) {
            return true;
        }

        return $this->roles()->whereIn('slug', $roles)->exists();
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->roles()->whereHas('permissions', fn ($query) => $query->where('slug', $permission))->exists()) {
            return true;
        }

        return $this->roles()->exists()
            ? false
            : Role::query()
                ->where('slug', $this->role)
                ->whereHas('permissions', fn ($query) => $query->where('slug', $permission))
                ->exists();
    }
}
