<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Admin top-bar search across products, orders, payments and users,
 * limited to the sections the admin is allowed to see.
 */
class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $term = trim((string) $request->query('q'));
        $user = $request->user();
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';
        $results = ['products' => collect(), 'orders' => collect(), 'payments' => collect(), 'users' => collect()];

        if (mb_strlen($term) >= 2) {
            if ($user->can('view_products')) {
                $results['products'] = Product::query()
                    ->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('slug', 'like', $like)->orWhere('id', (int) $term))
                    ->with('user:id,name,role_id')->limit(10)->get();
            }

            if ($user->can('manage_orders')) {
                $results['orders'] = Order::query()
                    ->where(fn ($q) => $q->where('order_number', 'like', $like)->orWhere('customer_email', 'like', $like)
                        ->orWhere('customer_name', 'like', $like)->orWhere('transaction_id', 'like', $like))
                    ->latest('id')->limit(10)->get();
            }

            if ($user->can('manage_payments')) {
                $results['payments'] = Payment::query()
                    ->where(fn ($q) => $q->where('transaction_id', 'like', $like)->orWhere('sender_name', 'like', $like))
                    ->with('order:id,order_number')->latest('id')->limit(10)->get();
            }

            if ($user->can('manage_users')) {
                $results['users'] = User::query()
                    ->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('username', 'like', $like))
                    ->limit(10)->get();
            }
        }

        return view('admin.search', [
            'term' => $term,
            'results' => $results,
            'total' => collect($results)->sum(fn (Collection $c) => $c->count()),
        ]);
    }
}
