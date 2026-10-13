<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;
use App\Notifications\ProductApproved;
use App\Notifications\ProductRejected;
use App\Notifications\ProductSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_publisher_submits_product_with_files_and_it_stays_private_until_approved(): void
    {
        Notification::fake();
        $publisher = User::factory()->publisher()->create();
        $reviewer = $this->superAdmin();
        $category = $this->category();

        $response = $this->actingAs($publisher)->post('/publisher/products', $this->payload($category->id, ['action' => 'submit']) + [
            'image' => UploadedFile::fake()->image('cover.jpg', 1200, 900),
            'gallery' => [UploadedFile::fake()->image('g1.png', 800, 600)],
            'file' => UploadedFile::fake()->create('theme.zip', 200, 'application/zip'),
        ]);

        $product = Product::query()->where('name', 'Premium WordPress Business Theme')->firstOrFail();
        $response->assertRedirect(route('publisher.products.show', $product));

        // Slug, keywords, files
        $this->assertSame('premium-wordpress-business-theme', $product->slug);
        $this->assertSame(['business theme', 'wordpress theme'], $product->keywords->pluck('name')->sort()->values()->all());
        Storage::disk('local')->assertExists($product->file_path);
        Storage::disk('public')->assertExists($product->image);
        Storage::disk('public')->assertExists($product->thumbnail);
        $this->assertCount(1, $product->images);
        $this->assertStringNotContainsString('theme.zip', $product->file_path, 'Original filenames are never used for storage');
        $this->assertSame(1999.0, (float) $product->effective_price);

        // Workflow state
        $this->assertSame(ProductStatus::Pending, $product->status);
        $this->assertSame(ApprovalStatus::Pending, $product->approval_status);
        Notification::assertSentTo($reviewer, ProductSubmitted::class);

        // The owner may preview it, but it is not public before approval.
        $this->get('/product/'.$product->slug)->assertOk()->assertSee('not visible to the public');
        auth()->logout();
        $this->get('/product/'.$product->slug)->assertNotFound();
        $this->get('/products')->assertDontSee($product->name);
    }

    public function test_draft_products_are_not_public(): void
    {
        $publisher = User::factory()->publisher()->create();

        $this->actingAs($publisher)->post('/publisher/products', $this->payload($this->category()->id, ['action' => 'draft']))
            ->assertSessionHasNoErrors();

        $product = Product::query()->latest('id')->firstOrFail();
        $this->assertSame(ApprovalStatus::Draft, $product->approval_status);

        auth()->logout();
        $this->get('/product/'.$product->slug)->assertNotFound();
    }

    public function test_submission_requires_image_and_file(): void
    {
        $publisher = User::factory()->publisher()->create();

        $this->actingAs($publisher)->post('/publisher/products', $this->payload($this->category()->id, ['action' => 'submit']))
            ->assertSessionHasErrors('submit');

        $product = Product::query()->latest('id')->firstOrFail();
        $this->assertSame(ApprovalStatus::Draft, $product->approval_status, 'Incomplete products stay in draft');
    }

    public function test_admin_approves_and_product_becomes_visible(): void
    {
        Notification::fake();
        $product = Product::factory()->pending()->create();
        $admin = User::factory()->admin(['approve_products', 'manage_products'])->create();

        $this->actingAs($admin)->post(route('admin.products.approve', $product), ['publish' => 1])->assertSessionHas('success');

        $product->refresh();
        $this->assertSame(ApprovalStatus::Approved, $product->approval_status);
        $this->assertSame(ProductStatus::Published, $product->status);
        $this->assertNotNull($product->published_at);
        $this->assertSame($admin->id, $product->approved_by);
        Notification::assertSentTo($product->user, ProductApproved::class);

        $this->get('/product/'.$product->slug)->assertOk()->assertSee($product->name);
        $this->get('/products')->assertSee($product->name);
    }

    public function test_approve_without_publishing_keeps_product_hidden(): void
    {
        $product = Product::factory()->pending()->create();

        $this->actingAs($this->superAdmin())->post(route('admin.products.approve', $product), ['publish' => 0]);

        $this->assertSame(ProductStatus::Approved, $product->fresh()->status);
        auth()->logout();
        $this->get('/product/'.$product->slug)->assertNotFound();
    }

    public function test_admin_rejects_with_reason_and_publisher_resubmits(): void
    {
        Notification::fake();
        $product = Product::factory()->pending()->create();
        $publisher = $product->user;

        // A reason is mandatory
        $this->actingAs($this->superAdmin())->post(route('admin.products.reject', $product), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->post(route('admin.products.reject', $product), ['reason' => 'Screenshots are too small.'])
            ->assertSessionHas('success');

        $product->refresh();
        $this->assertSame(ApprovalStatus::Rejected, $product->approval_status);
        $this->assertSame('Screenshots are too small.', $product->rejection_reason);
        Notification::assertSentTo($publisher, ProductRejected::class);

        // Publisher sees the reason, edits and resubmits.
        $this->actingAs($publisher)->get(route('publisher.products.show', $product))->assertSee('Screenshots are too small.');

        $this->put(route('publisher.products.update', $product), $this->payload($product->category_id, [
            'name' => 'Improved Product',
            'slug' => $product->slug,
            'action' => 'submit',
        ]))->assertRedirect(route('publisher.products.show', $product));

        $product->refresh();
        $this->assertSame(ApprovalStatus::Pending, $product->approval_status);
        $this->assertNull($product->rejection_reason);
        $this->assertSame('Improved Product', $product->name);
        $this->assertTrue($product->statusHistories()->where('note', 'Resubmitted for review')->exists());
    }

    public function test_editing_a_published_product_sends_it_back_to_review(): void
    {
        $product = $this->publishedProduct();

        $this->actingAs($product->user)->put(route('publisher.products.update', $product), $this->payload($product->category_id, [
            'name' => 'Changed name',
            'slug' => $product->slug,
            'action' => 'submit',
        ]));

        $product->refresh();
        $this->assertSame(ApprovalStatus::Pending, $product->approval_status);
        $this->assertFalse($product->isPublished());
    }

    public function test_publisher_cannot_edit_or_view_another_publishers_product(): void
    {
        $product = Product::factory()->create();
        $other = User::factory()->publisher()->create();

        $this->actingAs($other)->get(route('publisher.products.edit', $product))->assertForbidden();
        $this->actingAs($other)->get(route('publisher.products.show', $product))->assertForbidden();
        $this->actingAs($other)->put(route('publisher.products.update', $product), $this->payload($product->category_id, ['action' => 'draft']))->assertForbidden();
    }

    public function test_publisher_cannot_publish_or_feature_through_form_fields(): void
    {
        $publisher = User::factory()->publisher()->create();

        $this->actingAs($publisher)->post('/publisher/products', $this->payload($this->category()->id, [
            'action' => 'draft',
            'status' => 'published',
            'approval_status' => 'approved',
            'is_featured' => '1',
            'download_count' => 999,
        ]));

        $product = Product::query()->latest('id')->firstOrFail();
        $this->assertSame(ProductStatus::Draft, $product->status);
        $this->assertFalse($product->is_featured);
        $this->assertSame(0, $product->download_count);
    }

    public function test_dangerous_files_are_rejected(): void
    {
        $publisher = User::factory()->publisher()->create();
        $category = $this->category();

        foreach (['shell.php', 'run.exe', 'theme.php.zip', 'page.html'] as $name) {
            $this->actingAs($publisher)->post('/publisher/products', $this->payload($category->id, ['action' => 'draft']) + [
                'file' => UploadedFile::fake()->create($name, 10),
            ])->assertSessionHasErrors('file');
        }
    }

    public function test_sale_price_must_be_lower_than_price(): void
    {
        $publisher = User::factory()->publisher()->create();

        $this->actingAs($publisher)->post('/publisher/products', $this->payload($this->category()->id, [
            'action' => 'draft', 'price' => 1000, 'sale_price' => 1500,
        ]))->assertSessionHasErrors('sale_price');
    }

    public function test_slug_change_keeps_redirect_and_slugs_are_unique(): void
    {
        $product = $this->publishedProduct(['slug' => 'old-slug']);
        $admin = $this->superAdmin();

        $this->actingAs($admin)->put(route('admin.products.update', $product), $this->payload($product->category_id, [
            'slug' => 'new-slug', 'user_id' => $product->user_id, 'action' => 'save',
        ]))->assertSessionHasNoErrors();

        $this->get('/product/old-slug')->assertRedirect('/product/new-slug')->assertStatus(301);

        // A second product with the same name gets a unique slug.
        $this->post(route('admin.products.store'), $this->payload($product->category_id, [
            'name' => 'Dup', 'slug' => 'new-slug', 'user_id' => $admin->id, 'action' => 'draft',
        ]));
        $this->assertSame('new-slug-2', Product::query()->where('name', 'Dup')->value('slug'));
    }

    public function test_admin_can_create_free_product_and_publish_directly(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('admin.products.store'), $this->payload($this->category()->id, [
            'name' => 'Free Icons', 'is_free' => '1', 'user_id' => $admin->id, 'action' => 'publish',
        ]) + [
            'image' => UploadedFile::fake()->image('cover.png', 800, 600),
            'file' => UploadedFile::fake()->create('icons.zip', 50, 'application/zip'),
        ])->assertSessionHasNoErrors();

        $product = Product::query()->where('name', 'Free Icons')->firstOrFail();
        $this->assertTrue($product->is_free);
        $this->assertSame(0.0, (float) $product->price);
        $this->assertTrue($product->isPublished());
    }

    public function test_bulk_actions(): void
    {
        $products = Product::factory()->count(3)->pending()->create();

        $this->actingAs($this->superAdmin())->post(route('admin.products.bulk'), [
            'action' => 'approve',
            'ids' => $products->pluck('id')->all(),
        ])->assertSessionHas('success');

        $this->assertSame(3, Product::query()->published()->count());

        $this->post(route('admin.products.bulk'), ['action' => 'unpublish', 'ids' => $products->pluck('id')->all()]);
        $this->assertSame(0, Product::query()->published()->count());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function payload(int $categoryId, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Premium WordPress Business Theme',
            'category_id' => $categoryId,
            'product_type' => 'theme',
            'short_description' => 'A fast, responsive business theme.',
            'description' => "## Overview\n\nGreat theme.",
            'features' => "Responsive\nFast",
            'is_free' => '0',
            'price' => '2500',
            'sale_price' => '1999',
            'version' => '1.0.0',
            'keywords' => 'wordpress theme, business theme',
            'whatsapp_enabled' => '1',
            'action' => 'draft',
        ], $overrides);
    }
}
