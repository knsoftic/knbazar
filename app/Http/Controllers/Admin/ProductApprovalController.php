<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReasonRequest;
use App\Models\Product;
use App\Services\ProductApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductApprovalController extends Controller
{
    public function __construct(protected ProductApprovalService $approval) {}

    public function approve(Request $request, Product $product): RedirectResponse
    {
        $request->validate(['publish' => ['nullable', 'boolean'], 'note' => ['nullable', 'string', 'max:500']]);

        $publish = $request->boolean('publish', true);
        $this->approval->approve($product, $request->user(), $publish, $request->input('note'));

        return back()->with('success', '"'.$product->name.'" approved'.($publish ? ' and published.' : '. Publish it when ready.'));
    }

    public function reject(ReasonRequest $request, Product $product): RedirectResponse
    {
        $this->approval->reject($product, $request->user(), $request->validated('reason'));

        return back()->with('success', '"'.$product->name.'" rejected. The publisher has been notified.');
    }

    public function publish(Request $request, Product $product): RedirectResponse
    {
        $this->approval->publish($product, $request->user());

        return back()->with('success', '"'.$product->name.'" is now published.');
    }

    public function unpublish(Request $request, Product $product): RedirectResponse
    {
        $this->approval->unpublish($product, $request->user());

        return back()->with('success', '"'.$product->name.'" has been unpublished.');
    }

    public function toggleFeatured(Request $request, Product $product): RedirectResponse
    {
        $this->approval->setFeatured($product, ! $product->is_featured, $request->user());

        return back()->with('success', '"'.$product->name.'" '.($product->is_featured ? 'is now featured.' : 'is no longer featured.'));
    }
}
