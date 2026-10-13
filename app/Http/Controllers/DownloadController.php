<?php

namespace App\Http\Controllers;

use App\Exceptions\DownloadDeniedException;
use App\Models\Download;
use App\Models\Product;
use App\Services\DownloadService;
use App\Services\SeoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    /**
     * Customer downloads: purchased products with access info + download history.
     */
    public function index(Request $request, SeoService $seo): View
    {
        $user = $request->user();

        $permissions = $user->downloadPermissions()
            ->with(['product' => fn ($q) => $q->with('category:id,name,slug'), 'order:id,order_number,status,payment_status'])
            ->latest('id')
            ->get();

        $history = Download::query()
            ->where('user_id', $user->getKey())
            ->with('product:id,name,slug,is_free,deleted_at')
            ->latest('id')
            ->paginate(config('marketplace.pagination.account'))
            ->withQueryString();

        return view('account.downloads', [
            'permissions' => $permissions,
            'history' => $history,
            'seo' => $seo->defaults('My downloads')->noindex(),
        ]);
    }

    public function download(Request $request, Product $product, DownloadService $downloads): StreamedResponse|RedirectResponse
    {
        try {
            return $downloads->download($product, $request->user());
        } catch (DownloadDeniedException $e) {
            if ($e->requiresLogin) {
                return redirect()->guest(route('login'))->with('warning', $e->getMessage());
            }

            return redirect()
                ->to($product->trashed() ? route('home') : route('products.show', $product->slug))
                ->with('error', $e->getMessage());
        }
    }
}
