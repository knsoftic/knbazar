<?php

namespace App\Services;

use App\Models\DownloadPermission;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Session-based cart for paid digital products. Prices are always read
 * from the database, never from the session or the request.
 */
class CartService
{
    public const SESSION_KEY = 'cart.items';

    /** @var Collection<int, array<string, mixed>>|null */
    protected ?Collection $lines = null;

    public function __construct(protected Session $session) {}

    /**
     * @return array<int, int> product_id => quantity
     */
    public function items(): array
    {
        $items = $this->session->get(self::SESSION_KEY, []);

        return is_array($items) ? array_map('intval', $items) : [];
    }

    public function add(Product $product, int $quantity = 1, ?User $user = null): void
    {
        $this->assertPurchasable($product, $user);

        $items = $this->items();
        $items[$product->getKey()] = $this->clampQuantity(($items[$product->getKey()] ?? 0) + max(1, $quantity));

        $this->put($items);
    }

    public function update(int $productId, int $quantity): void
    {
        $items = $this->items();

        if (isset($items[$productId])) {
            $items[$productId] = $this->clampQuantity($quantity);
            $this->put($items);
        }
    }

    public function remove(int $productId): void
    {
        $items = $this->items();
        unset($items[$productId]);
        $this->put($items);
    }

    public function clear(): void
    {
        $this->session->forget(self::SESSION_KEY);
        $this->lines = null;
    }

    public function has(int $productId): bool
    {
        return array_key_exists($productId, $this->items());
    }

    public function count(): int
    {
        return count($this->items());
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /**
     * Cart lines with current database prices. Products that are no longer
     * purchasable are silently removed from the cart.
     *
     * @return Collection<int, array{product: Product, quantity: int, unit_price: float, price: float, discount: float, total: float}>
     */
    public function lines(): Collection
    {
        if ($this->lines !== null) {
            return $this->lines;
        }

        $items = $this->items();

        if (! $items) {
            return $this->lines = collect();
        }

        $products = Product::query()->visible()->paid()->forCard()->whereKey(array_keys($items))->get()->keyBy('id');

        if ($products->count() !== count($items)) {
            $this->put(array_intersect_key($items, $products->all()));
            $items = $this->items();
        }

        return $this->lines = $products->map(function (Product $product) use ($items) {
            $quantity = $this->clampQuantity($items[$product->getKey()] ?? 1);
            $unit = (float) $product->price;
            $price = $product->finalPrice();

            return [
                'product' => $product,
                'quantity' => $quantity,
                'unit_price' => $unit,
                'price' => $price,
                'discount' => round(($unit - $price) * $quantity, 2),
                'total' => round($price * $quantity, 2),
            ];
        })->values();
    }

    /**
     * @return array{subtotal: float, discount: float, total: float, count: int}
     */
    public function totals(): array
    {
        $lines = $this->lines();

        return [
            'subtotal' => round($lines->sum(fn ($line) => $line['unit_price'] * $line['quantity']), 2),
            'discount' => round($lines->sum('discount'), 2),
            'total' => round($lines->sum('total'), 2),
            'count' => $lines->count(),
        ];
    }

    public function maxQuantity(): int
    {
        return setting('cart_allow_quantity') ? max(1, (int) setting('cart_max_quantity', 5)) : 1;
    }

    public function assertPurchasable(Product $product, ?User $user = null): void
    {
        $isVisible = Product::query()->visible()->whereKey($product->getKey())->exists();

        if (! $isVisible) {
            throw ValidationException::withMessages(['cart' => 'This product is not available for purchase.']);
        }

        if ($product->is_free) {
            throw ValidationException::withMessages(['cart' => 'Free products do not need to be purchased - download them directly.']);
        }

        if ($user && $this->ownsProduct($user, $product)) {
            throw ValidationException::withMessages(['cart' => 'You already own "'.$product->name.'". Find it in your downloads.']);
        }
    }

    public function ownsProduct(User $user, Product $product): bool
    {
        return DownloadPermission::query()
            ->valid()
            ->where('user_id', $user->getKey())
            ->where('product_id', $product->getKey())
            ->exists();
    }

    protected function clampQuantity(int $quantity): int
    {
        return max(1, min($quantity, $this->maxQuantity()));
    }

    /**
     * @param  array<int, int>  $items
     */
    protected function put(array $items): void
    {
        $this->session->put(self::SESSION_KEY, $items);
        $this->lines = null;
    }
}
