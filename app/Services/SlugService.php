<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductSlugRedirect;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SlugService
{
    /**
     * Generate a unique, SEO-friendly slug for the given model class.
     *
     * @param  class-string<Model>  $modelClass
     */
    public function unique(string $modelClass, string $value, ?int $ignoreId = null, string $column = 'slug'): string
    {
        $base = Str::limit(Str::slug($value), 180, '');
        $base = trim($base, '-') ?: Str::lower(Str::random(8));

        $slug = $base;
        $suffix = 2;

        while ($this->exists($modelClass, $slug, $ignoreId, $column)) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /**
     * A unique username derived from a name or email, avoiding reserved words.
     */
    public function uniqueUsername(string $seed): string
    {
        $base = Str::limit(Str::slug(Str::before($seed, '@'), '_'), 30, '') ?: 'user';
        $reserved = config('marketplace.reserved_usernames', []);

        if (mb_strlen($base) < 3 || in_array($base, $reserved, true)) {
            $base .= '_'.Str::lower(Str::random(4));
        }

        $username = $base;
        $suffix = 2;

        while (User::withTrashed()->where('username', $username)->exists()) {
            $username = $base.$suffix++;
        }

        return $username;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    public function exists(string $modelClass, string $slug, ?int $ignoreId = null, string $column = 'slug'): bool
    {
        $query = $modelClass::query();

        if (in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)) {
            $query->withTrashed();
        }

        if ($ignoreId) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->where($column, $slug)->exists()) {
            return true;
        }

        // A product slug must not shadow another product's redirect history.
        if ($modelClass === Product::class) {
            return ProductSlugRedirect::query()
                ->where('old_slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('product_id', '!=', $ignoreId))
                ->exists();
        }

        return false;
    }
}
