<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsCatalogFilters;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSlugRedirect;
use App\Services\CartService;
use App\Services\DownloadService;
use App\Services\ProductSearchService;
use App\Services\SeoService;
use App\Services\WhatsAppOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductController extends Controller
{
    use BuildsCatalogFilters;

    public function index(Request $request, ProductSearchService $search, SeoService $seo): View
    {
        $filters = $this->catalogFilters($request);

        if ($slug = $request->query('category')) {
            $filters['category_id'] = Category::query()->active()->where('slug', (string) $slug)->value('id') ?? 0;
        }

        $meta = $seo->defaults('All products', 'Browse all digital products on '.setting('site_name').'.');
        $meta->canonical = route('products.index');

        if (array_diff(array_keys($filters), ['sort'])) {
            $meta->noindex(); // filtered variations are not indexed
        }

        return view('products.index', [
            'products' => $search->paginate($filters),
            'filters' => $filters + ['category' => $request->query('category')],
            'heading' => 'All products',
            'formAction' => route('products.index'),
            'seo' => $meta,
        ]);
    }

    public function show(Request $request, string $slug, SeoService $seo, CartService $cart, DownloadService $downloads, WhatsAppOrderService $whatsapp): View|RedirectResponse
    {
        $product = Product::query()
            ->where('slug', $slug)
            ->with(['category', 'user.publisherProfile', 'images', 'keywords'])
            ->first();

        if (! $product) {
            $redirect = ProductSlugRedirect::query()->where('old_slug', $slug)->with('product:id,slug')->first();
            abort_unless($redirect?->product, 404);

            return redirect()->route('products.show', $redirect->product->slug, 301);
        }

        $user = $request->user();
        $isVisible = Product::query()->visible()->whereKey($product->getKey())->exists();
        $isPreview = ! $isVisible && ($user?->can('view_products') || $product->user_id === $user?->getKey());

        abort_unless($isVisible || $isPreview, 404);

        if ($isVisible) {
            $this->countView($request, $product);
        }

        $related = Product::query()->visible()->forCard()
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->getKey())
            ->orderByDesc('sales_count')
            ->latest('published_at')
            ->limit(4)
            ->get();

        $meta = $seo->forProduct($product);
        if ($isPreview) {
            $meta->noindex();
        }

        return view('products.show', [
            'product' => $product,
            'related' => $related,
            'isPreview' => $isPreview,
            'inCart' => $cart->has($product->getKey()),
            'permission' => $user && ! $product->is_free ? $downloads->usablePermission($user, $product) : null,
            'whatsappUrl' => $whatsapp->isAvailableFor($product) ? $whatsapp->url($product) : null,
            'seo' => $meta,
        ]);
    }

    /**
     * Count one view per product per session, without touching updated_at.
     */
    protected function countView(Request $request, Product $product): void
    {
        $viewed = $request->session()->get('viewed_products', []);

        if (! in_array($product->getKey(), $viewed, true)) {
            DB::table('products')->where('id', $product->getKey())->increment('view_count');
            $viewed[] = $product->getKey();
            $request->session()->put('viewed_products', array_slice($viewed, -200));
        }
    }
}
