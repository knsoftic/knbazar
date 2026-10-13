<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;
use App\Notifications\ProductApproved;
use App\Notifications\ProductRejected;
use App\Notifications\ProductSubmitted;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Product workflow:
 * Draft -> Pending Review -> Approved (+ Published) or Rejected -> edit -> Pending Review ...
 * Publisher products never become public without passing through approve().
 */
class ProductApprovalService
{
    public function __construct(
        protected ActivityLogger $logger,
        protected NotificationService $notifications,
    ) {}

    /**
     * Publisher submits (or resubmits) a product for review.
     */
    public function submit(Product $product, User $actor): Product
    {
        $missing = $this->missingRequirements($product);

        if ($missing) {
            throw ValidationException::withMessages([
                'submit' => 'Please complete the product before submitting: '.implode(', ', $missing).'.',
            ]);
        }

        $limit = (int) setting('publisher_max_pending_products', 0);
        if ($limit > 0 && ! $product->isPendingReview()) {
            $pending = Product::query()
                ->where('user_id', $product->user_id)
                ->where('approval_status', ApprovalStatus::Pending->value)
                ->count();

            if ($pending >= $limit) {
                throw ValidationException::withMessages([
                    'submit' => "You already have {$pending} products waiting for review. Please wait for them to be reviewed.",
                ]);
            }
        }

        $wasRejected = $product->isRejected();

        $this->transition($product, $actor, [
            'status' => ProductStatus::Pending,
            'approval_status' => ApprovalStatus::Pending,
            'submitted_at' => now(),
            'rejection_reason' => null,
        ], $wasRejected ? 'Resubmitted for review' : 'Submitted for review');

        $this->notifications->send($this->notifications->staffWithPermission('approve_products'), new ProductSubmitted($product));
        $this->logger->log($wasRejected ? 'product.resubmitted' : 'product.submitted', $product, "Product \"{$product->name}\" submitted for review", user: $actor);

        return $product;
    }

    /**
     * A publisher saved changes without submitting: back to draft (hidden).
     */
    public function moveToDraft(Product $product, User $actor): Product
    {
        if ($product->approval_status === ApprovalStatus::Draft && $product->status === ProductStatus::Draft) {
            return $product;
        }

        return $this->transition($product, $actor, [
            'status' => ProductStatus::Draft,
            'approval_status' => ApprovalStatus::Draft,
        ], 'Saved as draft');
    }

    public function approve(Product $product, User $admin, bool $publish = true, ?string $note = null): Product
    {
        $this->transition($product, $admin, [
            'approval_status' => ApprovalStatus::Approved,
            'status' => $publish ? ProductStatus::Published : ProductStatus::Approved,
            'approved_at' => now(),
            'approved_by' => $admin->getKey(),
            'rejection_reason' => null,
            'published_at' => $publish ? ($product->published_at ?? now()) : $product->published_at,
        ], $note ?: ($publish ? 'Approved and published' : 'Approved'));

        if ($product->user_id !== $admin->getKey()) {
            $this->notifications->send($product->loadMissing('user')->user, new ProductApproved($product));
        }

        $this->logger->log('product.approved', $product, "Product \"{$product->name}\" approved".($publish ? ' and published' : ''), user: $admin);

        return $product;
    }

    public function reject(Product $product, User $admin, string $reason): Product
    {
        $this->transition($product, $admin, [
            'approval_status' => ApprovalStatus::Rejected,
            'status' => ProductStatus::Rejected,
            'rejection_reason' => $reason,
        ], $reason);

        if ($product->user_id !== $admin->getKey()) {
            $this->notifications->send($product->loadMissing('user')->user, new ProductRejected($product, $reason));
        }

        $this->logger->log('product.rejected', $product, "Product \"{$product->name}\" rejected", ['reason' => $reason], $admin);

        return $product;
    }

    public function publish(Product $product, User $admin): Product
    {
        if ($product->approval_status !== ApprovalStatus::Approved) {
            throw ValidationException::withMessages(['status' => 'Only approved products can be published. Approve the product first.']);
        }

        $this->transition($product, $admin, [
            'status' => ProductStatus::Published,
            'published_at' => $product->published_at ?? now(),
        ], 'Published');

        $this->logger->log('product.published', $product, "Product \"{$product->name}\" published", user: $admin);

        return $product;
    }

    public function unpublish(Product $product, User $admin): Product
    {
        $this->transition($product, $admin, ['status' => ProductStatus::Unpublished], 'Unpublished');
        $this->logger->log('product.unpublished', $product, "Product \"{$product->name}\" unpublished", user: $admin);

        return $product;
    }

    public function setFeatured(Product $product, bool $featured, User $admin): Product
    {
        $product->forceFill(['is_featured' => $featured])->save();
        CatalogCache::flush();
        $this->logger->log($featured ? 'product.featured' : 'product.unfeatured', $product, "Product \"{$product->name}\" ".($featured ? 'featured' : 'unfeatured'), user: $admin);

        return $product;
    }

    /**
     * @return array<int, string>
     */
    public function missingRequirements(Product $product): array
    {
        $missing = [];

        if (blank($product->name)) {
            $missing[] = 'name';
        }
        if (! $product->category_id) {
            $missing[] = 'category';
        }
        if (blank($product->short_description) && blank($product->description)) {
            $missing[] = 'description';
        }
        if (blank($product->image)) {
            $missing[] = 'main image';
        }
        if (! $product->hasFile()) {
            $missing[] = 'downloadable file';
        }
        if (! $product->is_free && (float) $product->price <= 0) {
            $missing[] = 'price';
        }

        return $missing;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function transition(Product $product, ?User $actor, array $attributes, ?string $note = null): Product
    {
        DB::transaction(function () use ($product, $actor, $attributes, $note) {
            $from = $product->status?->value;
            $product->forceFill($attributes)->save();

            $product->statusHistories()->create([
                'user_id' => $actor?->getKey(),
                'from_status' => $from,
                'to_status' => $product->status->value,
                'note' => $note,
            ]);
        });

        CatalogCache::flush();

        return $product;
    }
}
