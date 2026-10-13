<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApprovalStatus;
use App\Enums\ProductStatus;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\FileUploadService;
use App\Services\ProductApprovalService;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $products,
        protected ProductApprovalService $approval,
        protected ActivityLogger $logger,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'status', 'approval', 'type', 'category', 'publisher', 'featured']);

        $products = Product::query()
            ->with(['user:id,name,username,role_id', 'category:id,name'])
            ->when($filters['q'] ?? null, function ($q, $term) {
                $like = '%'.$term.'%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('slug', 'like', $like)->orWhere('id', (int) $term));
            })
            ->when(in_array($filters['status'] ?? null, ProductStatus::values(), true), fn ($q) => $q->where('status', $filters['status']))
            ->when(in_array($filters['approval'] ?? null, ApprovalStatus::values(), true), fn ($q) => $q->where('approval_status', $filters['approval']))
            ->when(($filters['type'] ?? null) === 'free', fn ($q) => $q->free())
            ->when(($filters['type'] ?? null) === 'paid', fn ($q) => $q->paid())
            ->when($filters['category'] ?? null, fn ($q, $id) => $q->where('category_id', (int) $id))
            ->when($filters['publisher'] ?? null, fn ($q, $id) => $q->where('user_id', (int) $id))
            ->when(! empty($filters['featured']), fn ($q) => $q->featured())
            ->orderByRaw('CASE WHEN approval_status = ? THEN 0 ELSE 1 END', [ApprovalStatus::Pending->value])
            ->latest('id')
            ->paginate(config('marketplace.pagination.admin'))
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'filters' => $filters,
            'categories' => Category::query()->ordered()->get(['id', 'name']),
        ]);
    }

    public function show(Product $product): View
    {
        $product->load(['user.publisherProfile', 'category', 'images', 'keywords', 'approver:id,name', 'statusHistories.user:id,name']);

        return view('admin.products.show', [
            'product' => $product,
            'missing' => $this->approval->missingRequirements($product),
            'fileExists' => $product->file_path && Storage::disk(FileUploadService::PRIVATE_DISK)->exists($product->file_path),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.products.form', [
            'product' => new Product(['is_free' => $request->query('type') === 'free', 'whatsapp_enabled' => true, 'product_type' => 'other']),
            'categories' => Category::query()->ordered()->get(['id', 'name', 'is_active']),
            'owners' => $this->owners(),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $owner = User::query()->findOrFail($request->validated('user_id'));
        $this->guardOwner($owner);

        $product = $this->products->create($owner, $request->productData() + ['keywords' => $request->validated('keywords')], $request->uploads());
        $this->logger->log('product.created', $product, "Product \"{$product->name}\" created by admin");

        return $this->afterSave($request, $product, 'Product created.');
    }

    public function edit(Product $product): View
    {
        $product->load(['images', 'keywords']);

        return view('admin.products.form', [
            'product' => $product,
            'categories' => Category::query()->ordered()->get(['id', 'name', 'is_active']),
            'owners' => $this->owners(),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $owner = User::query()->findOrFail($request->validated('user_id'));
        $this->guardOwner($owner);

        $this->products->update($product, $request->productData() + ['keywords' => $request->validated('keywords')], $request->uploads());

        if ($product->user_id !== $owner->getKey()) {
            $product->forceFill(['user_id' => $owner->getKey()])->save();
        }

        $this->logger->log('product.updated', $product, "Product \"{$product->name}\" updated by admin");

        return $this->afterSave($request, $product, 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->products->delete($product);
        $this->logger->log('product.deleted', $product, "Product \"{$product->name}\" deleted");

        return redirect()->route('admin.products.index')->with('success', 'Product deleted. Existing buyers can no longer download it.');
    }

    public function destroyImage(Product $product, ProductImage $image): RedirectResponse
    {
        abort_unless($image->product_id === $product->getKey(), 404);
        $this->products->deleteGalleryImage($image);

        return back()->with('success', 'Gallery image removed.');
    }

    /**
     * Download the product file for review (not tracked as a customer download).
     */
    public function file(Product $product): StreamedResponse
    {
        $disk = Storage::disk(FileUploadService::PRIVATE_DISK);
        abort_unless($product->file_path && $disk->exists($product->file_path), 404, 'File not found.');

        $this->logger->log('product.file_reviewed', $product, "Product file of \"{$product->name}\" downloaded for review");

        return $disk->download($product->file_path, $product->file_name ?: basename($product->file_path));
    }

    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['approve', 'publish', 'unpublish', 'feature', 'unfeature', 'delete'])],
            'ids' => ['required', 'array', 'max:200'],
            'ids.*' => ['integer'],
        ], ['ids.required' => 'Select at least one product.']);

        $user = $request->user();
        $ability = $data['action'] === 'approve' ? 'approve_products' : 'manage_products';
        abort_unless($user->can($ability), 403);

        $done = 0;
        $skipped = 0;

        foreach (Product::query()->with('user')->whereKey($data['ids'])->get() as $product) {
            try {
                match ($data['action']) {
                    'approve' => $this->approval->approve($product, $user),
                    'publish' => $this->approval->publish($product, $user),
                    'unpublish' => $this->approval->unpublish($product, $user),
                    'feature' => $this->approval->setFeatured($product, true, $user),
                    'unfeature' => $this->approval->setFeatured($product, false, $user),
                    'delete' => $this->products->delete($product),
                };
                $done++;
            } catch (ValidationException) {
                $skipped++;
            }
        }

        $this->logger->log('product.bulk_'.$data['action'], null, "Bulk {$data['action']} on {$done} products", ['ids' => $data['ids']]);

        return back()->with('success', "Bulk action applied to {$done} product(s).".($skipped ? " {$skipped} skipped (not eligible)." : ''));
    }

    /**
     * Admin saves: "publish" approves and publishes immediately, "draft" keeps
     * it hidden, "save" keeps the current workflow state.
     */
    protected function afterSave(ProductRequest $request, Product $product, string $message): RedirectResponse
    {
        $user = $request->user();
        $product->forceFill(['is_featured' => $request->boolean('is_featured')])->save();

        match ($request->validated('action')) {
            'publish' => $user->can('approve_products')
                ? $this->approval->approve($product, $user, true, 'Created/updated and published by admin')
                : null,
            'draft' => $this->approval->moveToDraft($product, $user),
            default => null,
        };

        return redirect()->route('admin.products.show', $product)->with('success', $message);
    }

    /**
     * @return Collection<int, User>
     */
    protected function owners()
    {
        return User::query()
            ->whereHas('role', fn ($q) => $q->whereIn('slug', [RoleName::Publisher->value, RoleName::Admin->value, RoleName::SuperAdmin->value]))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role_id']);
    }

    protected function guardOwner(User $owner): void
    {
        if (! $owner->isPublisher() && ! $owner->isAdmin()) {
            throw ValidationException::withMessages(['user_id' => 'Products can only belong to publishers or staff accounts.']);
        }
    }
}
