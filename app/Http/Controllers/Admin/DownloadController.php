<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Download;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DownloadController extends Controller
{
    public function __invoke(Request $request): View
    {
        $downloads = Download::query()
            ->with(['user:id,name,email,role_id', 'product:id,name,slug,deleted_at', 'order:id,order_number'])
            ->when($request->filled('product'), fn ($q) => $q->where('product_id', (int) $request->query('product')))
            ->when($request->filled('user'), fn ($q) => $q->where('user_id', (int) $request->query('user')))
            ->when($request->query('type') === 'free', fn ($q) => $q->where('is_free', true))
            ->when($request->query('type') === 'paid', fn ($q) => $q->where('is_free', false))
            ->when($request->filled('q'), function ($q) use ($request) {
                $like = '%'.$request->query('q').'%';
                $q->where(fn ($w) => $w->whereHas('product', fn ($p) => $p->withTrashed()->where('name', 'like', $like))
                    ->orWhereHas('user', fn ($u) => $u->where('email', 'like', $like)->orWhere('name', 'like', $like))
                    ->orWhere('ip_address', 'like', $like));
            })
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->latest('id')
            ->paginate(config('marketplace.pagination.admin'))
            ->withQueryString();

        return view('admin.downloads.index', ['downloads' => $downloads]);
    }
}
