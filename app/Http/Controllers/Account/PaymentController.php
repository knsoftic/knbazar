<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentSubmitRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Services\FileUploadService;
use App\Services\PaymentService;
use App\Services\SeoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    /**
     * Payment instructions + transaction details form.
     */
    public function create(Order $order, SeoService $seo): View|RedirectResponse
    {
        $this->authorize('view', $order);

        if (! $order->canSubmitPayment()) {
            return redirect()->route('orders.show', $order)->with('info', 'This order does not need a payment submission right now.');
        }

        $order->load(['items', 'gateway', 'payment']);

        return view('account.orders.pay', [
            'order' => $order,
            'gateways' => PaymentGateway::query()->active()->ordered()->get(),
            'lastPayment' => $order->payment,
            'seo' => $seo->defaults('Pay for order '.$order->order_number)->noindex(),
        ]);
    }

    public function store(PaymentSubmitRequest $request, Order $order, PaymentService $payments): RedirectResponse
    {
        $payments->submit($order, $request->user(), $request->safe()->except('proof'), $request->file('proof'));

        return redirect()->route('orders.show', $order)
            ->with('success', 'Thank you! Your payment details were submitted and are now under review. You will be notified once verified.');
    }

    /**
     * Customers may view the screenshot they uploaded (private file).
     */
    public function proof(Order $order, Payment $payment): StreamedResponse
    {
        $this->authorize('view', $order);
        abort_unless($payment->order_id === $order->getKey() && $payment->proof_path, 404);

        $disk = Storage::disk(FileUploadService::PRIVATE_DISK);
        abort_unless($disk->exists($payment->proof_path), 404);

        return $disk->response($payment->proof_path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
