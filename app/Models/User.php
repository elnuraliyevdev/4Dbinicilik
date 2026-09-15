<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name', 'email', 'password', 'phone', 'ref_code', 'pin_hash', 'role',
    'total_lessons', 'used_lessons', 'remaining_lessons', 'pending_lessons',
    'active_package_id', 'package_expires_at',
])]
#[Hidden(['password', 'pin_hash', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'pin_hash' => 'hashed',
            'package_expires_at' => 'date',
            'locked_until' => 'datetime',
        ];
    }

    public function trainerProfile(): HasOne
    {
        return $this->hasOne(Trainer::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function familyMemberships(): HasMany
    {
        return $this->hasMany(FamilyMember::class);
    }

    public function activePackage(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'active_package_id');
    }

    public function creditTransactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class);
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }
}
