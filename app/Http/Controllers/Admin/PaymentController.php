<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentRecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReasonRequest;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Services\FileUploadService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function __construct(protected PaymentService $payments) {}

    public function index(Request $request): View
    {
        $status = $request->query('status');

        $payments = Payment::query()
            ->with(['order:id,order_number,customer_name,customer_email,total,currency', 'gateway:id,name'])
            ->when(in_array($status, PaymentRecordStatus::values(), true), fn ($q) => $q->where('status', $status))
            ->when($request->filled('gateway'), fn ($q) => $q->where('payment_gateway_id', (int) $request->query('gateway')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $like = '%'.$request->query('q').'%';
                $q->where(fn ($w) => $w->where('transaction_id', 'like', $like)->orWhere('sender_name', 'like', $like)
                    ->orWhereHas('order', fn ($o) => $o->where('order_number', 'like', $like)->orWhere('customer_name', 'like', $like)));
            })
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [PaymentRecordStatus::UnderReview->value])
            ->latest('id')
            ->paginate(config('marketplace.pagination.admin'))
            ->withQueryString();

        return view('admin.payments.index', [
            'payments' => $payments,
            'status' => $status,
            'gateways' => PaymentGateway::withTrashed()->ordered()->get(['id', 'name']),
        ]);
    }

    public function show(Payment $payment): View
    {
        $payment->load(['order.items', 'order.user', 'gateway', 'reviewer:id,name,role_id', 'statusHistories.user:id,name,role_id', 'user']);

        $duplicates = Payment::query()
            ->where('transaction_id', $payment->transaction_id)
            ->whereKeyNot($payment->getKey())
            ->with('order:id,order_number')
            ->get();

        return view('admin.payments.show', ['payment' => $payment, 'duplicates' => $duplicates]);
    }

    /**
     * Stream the private payment screenshot (never publicly accessible).
     */
    public function proof(Payment $payment): StreamedResponse
    {
        $disk = Storage::disk(FileUploadService::PRIVATE_DISK);
        abort_unless($payment->proof_path && $disk->exists($payment->proof_path), 404);

        return $disk->response($payment->proof_path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function approve(Request $request, Payment $payment): RedirectResponse
    {
        $request->validate(['note' => ['nullable', 'string', 'max:500']]);
        $this->payments->approve($payment, $request->user(), $request->input('note'));

        return back()->with('success', 'Payment approved. Order '.$payment->order->order_number.' is now paid and downloads are enabled.');
    }

    public function reject(ReasonRequest $request, Payment $payment): RedirectResponse
    {
        $this->payments->reject($payment, $request->user(), $request->validated('reason'));

        return back()->with('success', 'Payment rejected. The customer can see the reason and resubmit.');
    }
}
