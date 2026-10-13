<?php

namespace App\Policies;

use App\Enums\ApprovalStatus;
use App\Models\Product;
use App\Models\User;

/**
 * Publisher-side product authorisation. Admin-side access is controlled by
 * the permission gates (manage_products / approve_products).
 */
class ProductPolicy
{
    public function view(User $user, Product $product): bool
    {
        return $this->owns($user, $product);
    }

    public function update(User $user, Product $product): bool
    {
        return $this->owns($user, $product) && $user->canSubmitProducts();
    }

    public function submit(User $user, Product $product): bool
    {
        return $this->update($user, $product)
            && in_array($product->approval_status, [ApprovalStatus::Draft, ApprovalStatus::Rejected], true);
    }

    /**
     * Publishers cannot delete products that customers have bought, so
     * buyers never lose access to their downloads.
     */
    public function delete(User $user, Product $product): bool
    {
        return $this->owns($user, $product) && $product->sales_count === 0;
    }

    protected function owns(User $user, Product $product): bool
    {
        return $user->isPublisher() && $product->user_id === $user->getKey();
    }
}
