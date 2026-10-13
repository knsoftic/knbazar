<?php

namespace App\Models;

use App\Enums\PublisherStatus;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['name', 'username', 'email', 'password', 'phone', 'country', 'address'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $with = ['role'];

    /** @var array<int, string>|null */
    protected ?array $permissionSlugCache = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
        ];
    }

    // ------------------------------------------------------------------
    // Relationships
    // ------------------------------------------------------------------

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function publisherProfile(): HasOne
    {
        return $this->hasOne(PublisherProfile::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }

    public function downloadPermissions(): HasMany
    {
        return $this->hasMany(DownloadPermission::class);
    }

    public function soldItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'publisher_id');
    }

    // ------------------------------------------------------------------
    // Roles & permissions
    // ------------------------------------------------------------------

    public function roleName(): ?RoleName
    {
        return $this->role?->enum();
    }

    public function hasRole(RoleName|string ...$roles): bool
    {
        $current = $this->role?->slug;

        foreach ($roles as $role) {
            if ($current === ($role instanceof RoleName ? $role->value : $role)) {
                return true;
            }
        }

        return false;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(RoleName::SuperAdmin);
    }

    /**
     * True for Admin and Super Admin accounts (staff with panel access).
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(RoleName::SuperAdmin, RoleName::Admin);
    }

    public function isPublisher(): bool
    {
        return $this->hasRole(RoleName::Publisher);
    }

    public function isCustomer(): bool
    {
        return $this->hasRole(RoleName::Customer);
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    /**
     * @return array<int, string>
     */
    public function permissionSlugs(): array
    {
        if ($this->permissionSlugCache !== null) {
            return $this->permissionSlugCache;
        }

        if (! $this->hasRole(RoleName::Admin)) {
            return $this->permissionSlugCache = [];
        }

        return $this->permissionSlugCache = $this->permissions()->pluck('slug')->all();
    }

    public function hasPermission(string $slug): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return in_array($slug, $this->permissionSlugs(), true);
    }

    public function flushPermissionCache(): void
    {
        $this->permissionSlugCache = null;
        $this->unsetRelation('permissions');
    }

    /**
     * Publishers may submit products only when active and approved.
     */
    public function canSubmitProducts(): bool
    {
        return $this->isPublisher()
            && $this->isActive()
            && $this->publisherProfile?->status === PublisherStatus::Approved;
    }

    public function dashboardRoute(): string
    {
        return match (true) {
            $this->isAdmin() => route('admin.dashboard'),
            $this->isPublisher() => route('publisher.dashboard'),
            default => route('account.index'),
        };
    }

    // ------------------------------------------------------------------
    // Presentation helpers
    // ------------------------------------------------------------------

    public function avatarUrl(): ?string
    {
        return storage_url($this->avatar);
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];
        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= Str::upper(Str::substr($part, 0, 1));
        }

        return $initials ?: 'U';
    }

    public function publicName(): string
    {
        return $this->publisherProfile?->display_name ?: $this->name;
    }

    /**
     * Only send verification mail when the site requires verification.
     */
    public function sendEmailVerificationNotification(): void
    {
        if (setting('email_verification_required')) {
            parent::sendEmailVerificationNotification();
        }
    }
}
