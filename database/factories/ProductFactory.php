<?php

namespace Database\Factories;

use App\Enums\ApprovalStatus;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(3, true));

        return [
            'user_id' => User::factory()->publisher(),
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999999),
            'short_description' => fake()->sentence(14),
            'description' => fake()->paragraphs(3, true),
            'features' => "Responsive design\nWell documented\nFree updates",
            'requirements' => 'Any modern browser',
            'product_type' => 'template',
            'is_free' => false,
            'price' => 2500,
            'sale_price' => null,
            'version' => '1.0.0',
            'image' => 'products/images/demo.webp',
            'thumbnail' => 'products/images/demo.webp',
            'file_path' => 'products/files/test/'.Str::random(20).'.zip',
            'file_name' => 'product.zip',
            'file_size' => 1024,
            'file_type' => 'zip',
            'file_mime' => 'application/zip',
            'whatsapp_enabled' => true,
            'status' => ProductStatus::Draft,
            'approval_status' => ApprovalStatus::Draft,
        ];
    }

    public function free(): static
    {
        return $this->state(fn () => ['is_free' => true, 'price' => 0, 'sale_price' => null]);
    }

    public function onSale(float $salePrice = 1999): static
    {
        return $this->state(fn () => ['sale_price' => $salePrice]);
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => ProductStatus::Pending,
            'approval_status' => ApprovalStatus::Pending,
            'submitted_at' => now(),
        ]);
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => ProductStatus::Published,
            'approval_status' => ApprovalStatus::Approved,
            'approved_at' => now(),
            'published_at' => now(),
        ]);
    }

    public function rejected(string $reason = 'Please add better screenshots.'): static
    {
        return $this->state(fn () => [
            'status' => ProductStatus::Rejected,
            'approval_status' => ApprovalStatus::Rejected,
            'rejection_reason' => $reason,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn () => ['is_featured' => true]);
    }
}
