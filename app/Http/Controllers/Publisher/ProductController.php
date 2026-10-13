<?php

namespace App\Http\Controllers\Publisher;

use App\Enums\ApprovalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ActivityLogger;
use App\Services\FileUploadService;
use App\Services\ProductApprovalService;
use App\Services\ProductService;
use App\Services\SeoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $products,
        protected ProductApprovalService $approval,
    ) {}

    public function index(Request $request, SeoService $seo): View
    {
        $status = $request->query('status');

        $products = $request->user()->products()
            ->with('category:id,name')
            ->when(in_array($status, ApprovalStatus::values(), true), fn ($q) => $q->where('approval_status', $status))
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->query('q').'%'))
            ->latest('updated_at')
            ->paginate(config('marketplace.pagination.account'))
            ->withQueryString();

        $counts = $request->user()->products()
            ->selectRaw('approval_status, count(*) as total')
            ->groupBy('approval_status')
            ->pluck('total', 'approval_status');

        return view('publisher.products.index', [
            'products' => $products,
            'counts' => $counts,
            'status' => $status,
            'seo' => $seo->defaults('My products')->noindex(),
        ]);
    }

    public function create(SeoService $seo): View
    {
        return view('publisher.products.create', [
            'product' => new Product(['is_free' => false, 'whatsapp_enabled' => true, 'product_type' => 'other']),
            'categories' => Category::query()->active()->ordered()->get(['id', 'name']),
            'seo' => $seo->defaults('Add product')->noindex(),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $user = $request->user();
        $product = $this->products->create($user, $request->productData() + ['keywords' => $request->validated('keywords')], $request->uploads());

        return $this->afterSave($request, $product, 'Product saved as draft.');
    }

    public function show(Request $request, Product $product, SeoService $seo): View
    {
        $this->authorize('view', $product);

        $product->load(['category', 'images', 'keywords', 'statusHistories.user:id,name']);

        return view('publisher.products.show', [
            'product' => $product,
            'sales' => $product->orderItems()->whereHas('order', fn ($q) => $q->where('payment_status', 'approved'))->sum('total'),
            'seo' => $seo->defaults($product->name)->noindex(),
        ]);
    }

    public function edit(Product $product, SeoService $seo): View
    {
        $this->authorize('update', $product);

        $product->load(['images', 'keywords']);

        return view('publisher.products.edit', [
            'product' => $product,
            'categories' => Category::query()->active()->ordered()->get(['id', 'name']),
            'seo' => $seo->defaults('Edit '.$product->name)->noindex(),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $this->products->update($product, $request->productData() + ['keywords' => $request->validated('keywords')], $request->uploads());

        return $this->afterSave($request, $product, 'Changes saved as draft.');
    }

    public function submit(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('submit', $product);

        $this->approval->submit($product, $request->user());

        return redirect()->route('publisher.products.show', $product)->with('success', 'Your product was submitted for review. You will be notified once it has been reviewed.');
    }

    public function destroy(Request $request, Product $product, ActivityLogger $logger): RedirectResponse
    {
        if (! $request->user()->can('delete', $product)) {
            return back()->with('error', 'Products that have sales cannot be deleted. Contact the team to unpublish it instead.');
        }

        $this->products->delete($product);
        $logger->log('product.deleted', $product, "Product \"{$product->name}\" deleted by publisher");

        return redirect()->route('publisher.products.index')->with('success', 'Product deleted.');
    }

    public function destroyImage(Product $product, ProductImage $image): RedirectResponse
    {
        $this->authorize('update', $product);
        abort_unless($image->product_id === $product->getKey(), 404);

        $this->products->deleteGalleryImage($image);

        return back()->with('success', 'Gallery image removed.');
    }

    /**
     * Publishers can download their own uploaded file (not tracked as a sale download).
     */
    public function file(Product $product): StreamedResponse
    {
        $this->authorize('view', $product);

        $disk = Storage::disk(FileUploadService::PRIVATE_DISK);
        abort_unless($product->file_path && $disk->exists($product->file_path), 404);

        return $disk->download($product->file_path, $product->file_name ?: basename($product->file_path));
    }

    /**
     * Draft saves move the product to Draft; "Submit" sends it for review.
     * Editing a live product therefore always requires a new review.
     */
    protected function afterSave(ProductRequest $request, Product $product, string $draftMessage): RedirectResponse
    {
        $user = $request->user();

        if ($request->validated('action') === 'submit') {
            try {
                $this->approval->submit($product, $user);
            } catch (ValidationException $e) {
                // Unreviewed changes must never stay live.
                $this->approval->moveToDraft($product, $user);

                return redirect()->route('publisher.products.edit', $product)
                    ->with('warning', 'Your changes were saved as a draft, but the product could not be submitted yet.')
                    ->withErrors($e->errors());
            }

            return redirect()->route('publisher.products.show', $product)
                ->with('success', 'Product saved and submitted for review.');
        }

        $this->approval->moveToDraft($product, $user);

        return redirect()->route('publisher.products.edit', $product)->with('success', $draftMessage);
    }
}
