<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\NotificationStatus;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'role', 'is_active', 'is_super_admin', 'has_custom_access'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'is_super_admin' => 'boolean',
            'has_custom_access' => 'boolean',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function primaryRole(): ?string
    {
        return $this->role?->value ?? $this->getRoleNames()->first();
    }

    public function roleLabel(): string
    {
        return $this->appRole()?->label() ?? 'Belum memiliki role';
    }

    public function appRole(): ?UserRole
    {
        return $this->role instanceof UserRole
            ? $this->role
            : UserRole::tryFrom((string) $this->role);
    }

    public function dashboardRouteName(): string
    {
        return $this->appRole()?->dashboardRouteName() ?? 'dashboard';
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasAppRole(UserRole::Admin) && (bool) $this->is_super_admin;
    }

    public function hasFullSystemAccess(): bool
    {
        return $this->hasAppRole(UserRole::Admin) && ! $this->has_custom_access;
    }

    public function accessModeLabel(): string
    {
        if ($this->hasFullSystemAccess()) {
            return 'Full Access';
        }

        return $this->has_custom_access ? 'Akses Khusus' : 'Akses Role';
    }

    public function hasAppRole(UserRole|string $role): bool
    {
        return $this->appRole() === $this->normalizeRole($role);
    }

    /**
     * @param  array<int, UserRole|string>  $roles
     */
    public function hasAnyAppRole(array $roles): bool
    {
        return collect($roles)
            ->map(fn (UserRole|string $role) => $this->normalizeRole($role))
            ->contains(fn (UserRole $role) => $this->hasAppRole($role));
    }

    public function assignAppRole(UserRole|string $role): static
    {
        $role = $this->normalizeRole($role);

        $this->forceFill([
            'role' => $role,
        ])->save();

        $this->syncRoles([$role->value]);

        return $this;
    }

    private function normalizeRole(UserRole|string $role): UserRole
    {
        return $role instanceof UserRole ? $role : UserRole::from($role);
    }

    public function issuedCertificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'issued_by_user_id');
    }

    public function updatedCertificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'updated_by_user_id');
    }

    public function certificateRenewals(): HasMany
    {
        return $this->hasMany(CertificateRenewal::class, 'renewed_by_user_id');
    }

    public function systemNotifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function unreadSystemNotifications(): HasMany
    {
        return $this->systemNotifications()
            ->where('status', NotificationStatus::Unread->value);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function navigationItems(): BelongsToMany
    {
        return $this->belongsToMany(NavigationItem::class)
            ->withTimestamps();
    }
}
