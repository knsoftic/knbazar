<?php

namespace App\Models;

use App\Enums\RoleName;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description'])]
class Role extends Model
{
    /** @var array<string, int> */
    protected static array $idCache = [];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function enum(): ?RoleName
    {
        return RoleName::tryFrom($this->slug);
    }

    /**
     * Resolve a role id by slug (memoised per request).
     */
    public static function idFor(RoleName|string $role): int
    {
        $slug = $role instanceof RoleName ? $role->value : $role;

        return static::$idCache[$slug] ??= (int) static::query()->where('slug', $slug)->value('id');
    }

    public static function flushIdCache(): void
    {
        static::$idCache = [];
    }
}
