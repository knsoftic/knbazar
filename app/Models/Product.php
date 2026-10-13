<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Enums\ProductStatus;
use App\Enums\UserStatus;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Workflow-sensitive columns (status, approval_status, user_id, file paths,
 * featured flag, counters) are intentionally not fillable. They are only
 * changed through services such as ProductService and ProductApprovalService.
 */
#[Fillable([
    'category_id', 'name', 'slug', 'short_description', 'description', 'features', 'requirements',
    'product_type', 'is_free', 'price', 'sale_price', 'version', 'demo_url', 'documentation_url',
    'seo_title', 'seo_description', 'seo_keywords', 'focus_keyword', 'canonical_url',
    'og_title', 'og_description', 'whatsapp_enabled',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_free' => 'boolean',
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'effective_price' => 'decimal:2',
            'is_featured' => 'boolean',
            'whatsapp_enabled' => 'boolean',
            'status' => ProductStatus::class,
            'approval_status' => ApprovalStatus::class,
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
            'file_size' => 'integer',
            'view_count' => 'integer',
            'download_count' => 'integer',
            'sales_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            if ($product->is_free) {
                $product->price = 0;
                $product->sale_price = null;
            } elseif ($product->sale_price !== null && (float) $product->sale_price >= (float) $product->price) {
                $product->sale_price = null;
            }

            $product->effective_price = $product->finalPrice();
        });
    }

    // ------------------------------------------------------------------
    // Relationships
    // ------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Alias of user(): the publisher who owns the product.
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function keywords(): BelongsToMany
    {
        return $this->belongsToMany(Keyword::class, 'product_keywords');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }

    public function downloadPermissions(): HasMany
    {
        return $this->hasMany(DownloadPermission::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(ProductStatusHistory::class)->latest('id');
    }

    public function slugRedirects(): HasMany
    {
        return $this->hasMany(ProductSlugRedirect::class);
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    /**
     * Approved and published products (ignores category/publisher state).
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', ProductStatus::Published->value)
            ->where('approval_status', ApprovalStatus::Approved->value);
    }

    /**
     * Products that may appear on public marketplace pages.
     */
    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->published()
            ->whereHas('category', fn (Builder $q) => $q->where('is_active', true))
            ->whereHas('user', fn (Builder $q) => $q->where('status', UserStatus::Active->value));
    }

    #[Scope]
    protected function free(Builder $query): void
    {
        $query->where('is_free', true);
    }

    #[Scope]
    protected function paid(Builder $query): void
    {
        $query->where('is_free', false);
    }

    #[Scope]
    protected function featured(Builder $query): void
    {
        $query->where('is_featured', true);
    }

    #[Scope]
    protected function forCard(Builder $query): void
    {
        $query->with(['category:id,name,slug', 'user:id,name,username,role_id', 'user.publisherProfile:id,user_id,display_name']);
    }

    // ------------------------------------------------------------------
    // Pricing
    // ------------------------------------------------------------------

    public function finalPrice(): float
    {
        if ($this->is_free) {
            return 0.0;
        }

        return $this->hasDiscount() ? (float) $this->sale_price : (float) $this->price;
    }

    public function hasDiscount(): bool
    {
        return ! $this->is_free
            && $this->sale_price !== null
            && (float) $this->sale_price < (float) $this->price;
    }

    public function discountAmount(): float
    {
        return $this->hasDiscount() ? round((float) $this->price - (float) $this->sale_price, 2) : 0.0;
    }

    public function discountPercent(): int
    {
        if (! $this->hasDiscount() || (float) $this->price <= 0) {
            return 0;
        }

        return (int) round($this->discountAmount() / (float) $this->price * 100);
    }

    // ------------------------------------------------------------------
    // State helpers
    // ------------------------------------------------------------------

    public function isPublished(): bool
    {
        return $this->status === ProductStatus::Published
            && $this->approval_status === ApprovalStatus::Approved;
    }

    public function isPendingReview(): bool
    {
        return $this->approval_status === ApprovalStatus::Pending;
    }

    public function isRejected(): bool
    {
        return $this->approval_status === ApprovalStatus::Rejected;
    }

    public function isDraft(): bool
    {
        return $this->approval_status === ApprovalStatus::Draft;
    }

    public function hasFile(): bool
    {
        return filled($this->file_path);
    }

    // ------------------------------------------------------------------
    // Presentation helpers
    // ------------------------------------------------------------------

    public function url(): string
    {
        return route('products.show', $this->slug);
    }

    public function imageUrl(): string
    {
        return storage_url($this->image) ?? asset('assets/img/placeholder.svg');
    }

    public function thumbnailUrl(): string
    {
        return storage_url($this->thumbnail ?: $this->image) ?? asset('assets/img/placeholder.svg');
    }

    public function ogImageUrl(): ?string
    {
        return storage_url($this->og_image ?: $this->image);
    }

    public function typeLabel(): string
    {
        return config('marketplace.product_types.'.$this->product_type, ucfirst((string) $this->product_type));
    }

    /**
     * @return array<int, string>
     */
    public function featuresList(): array
    {
        return self::lines($this->features);
    }

    /**
     * @return array<int, string>
     */
    public function requirementsList(): array
    {
        return self::lines($this->requirements);
    }

    public function fileSizeHuman(): ?string
    {
        return $this->file_size ? format_bytes($this->file_size) : null;
    }

    public function publisherName(): string
    {
        return $this->user?->publicName() ?? 'Unknown';
    }

    /**
     * @return array<int, string>
     */
    private static function lines(?string $text): array
    {
        if (blank($text)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (string $line) => trim(ltrim(trim($line), '-*• ')),
            preg_split('/\r\n|\r|\n/', $text) ?: []
        )));
    }
}
