<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentRecordStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\User;
use App\Notifications\OrderCompleted;
use App\Notifications\PaymentApproved;
use App\Notifications\PaymentRejected;
use App\Notifications\PaymentSubmitted;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Manual payment flow:
 * customer submits transaction details (+ screenshot) -> Under Review ->
 * admin approves (order paid & completed, downloads granted) or rejects
 * with a reason (customer may resubmit).
 */
class PaymentService
{
    public function __construct(
        protected OrderService $orders,
        protected ImageService $images,
        protected ActivityLogger $logger,
        protected NotificationService $notifications,
    ) {}

    /**
     * @param  array{payment_gateway_id: int, transaction_id: string, sender_name?: string|null, sender_account?: string|null, amount: float|string, notes?: string|null}  $data
     */
    public function submit(Order $order, User $customer, array $data, ?UploadedFile $proof = null): Payment
    {
        if (! $order->canSubmitPayment()) {
            throw ValidationException::withMessages(['payment' => 'Payment details cannot be submitted for this order in its current state.']);
        }

        $gateway = PaymentGateway::query()->active()->find($data['payment_gateway_id']);
        if (! $gateway) {
            throw ValidationException::withMessages(['payment_gateway_id' => 'The selected payment method is not available.']);
        }

        if ($gateway->requires_proof && ! $proof) {
            throw ValidationException::withMessages(['proof' => 'A payment screenshot is required for '.$gateway->name.'.']);
        }

        $transactionId = trim($data['transaction_id']);
        $duplicate = Payment::query()
            ->where('payment_gateway_id', $gateway->getKey())
            ->where('transaction_id', $transactionId)
            ->where('status', '!=', PaymentRecordStatus::Rejected->value)
            ->exists();

        if ($duplicate) {
            $this->logger->security('Duplicate transaction ID submitted', ['order' => $order->order_number, 'gateway' => $gateway->slug]);

            throw ValidationException::withMessages(['transaction_id' => 'This transaction ID has already been submitted.']);
        }

        // Screenshots are private (payment data) and re-encoded to strip any payload.
        $proofPath = $proof
            ? $this->images->store($proof, 'payments/'.now()->format('Y/m'), 1600, disk: 'local', field: 'proof')['path']
            : null;

        try {
            $payment = DB::transaction(function () use ($order, $customer, $gateway, $data, $transactionId, $proofPath) {
                $payment = $order->payments()->create([
                    'payment_gateway_id' => $gateway->getKey(),
                    'user_id' => $customer->getKey(),
                    'amount' => round((float) $data['amount'], 2),
                    'currency' => $order->currency,
                    'transaction_id' => $transactionId,
                    'sender_name' => $data['sender_name'] ?? null,
                    'sender_account' => $data['sender_account'] ?? null,
                    'proof_path' => $proofPath,
                    'notes' => $data['notes'] ?? null,
                    'status' => PaymentRecordStatus::UnderReview,
                    'ip_address' => request()->ip(),
                ]);

                $this->recordHistory($payment, null, PaymentRecordStatus::UnderReview, $customer, 'Payment details submitted');

                $order->forceFill([
                    'payment_gateway_id' => $gateway->getKey(),
                    'payment_status' => PaymentStatus::Submitted,
                    'transaction_id' => $transactionId,
                    'payment_proof' => $proofPath,
                ]);
                $this->orders->transition($order, OrderStatus::PaymentSubmitted, $customer, 'Payment details submitted');

                return $payment;
            });
        } catch (Throwable $e) {
            $this->images->deleteFrom('local', $proofPath);

            throw $e;
        }

        $payment->setRelation('order', $order);
        $this->notifications->send($this->notifications->staffWithPermission('manage_payments'), new PaymentSubmitted($payment));
        $this->logger->log('payment.submitted', $payment, "Payment submitted for order {$order->order_number}", [
            'amount' => $payment->amount,
            'gateway' => $gateway->name,
        ], $customer);

        return $payment;
    }

    public function approve(Payment $payment, User $admin, ?string $note = null): Payment
    {
        if (! $payment->isReviewable()) {
            throw ValidationException::withMessages(['payment' => 'This payment has already been reviewed.']);
        }

        $order = $payment->order;

        DB::transaction(function () use ($payment, $order, $admin, $note) {
            $from = $payment->status;
            $payment->forceFill([
                'status' => PaymentRecordStatus::Approved,
                'rejection_reason' => null,
                'reviewed_by' => $admin->getKey(),
                'reviewed_at' => now(),
            ])->save();
            $this->recordHistory($payment, $from, PaymentRecordStatus::Approved, $admin, $note ?: 'Payment verified');

            $order->forceFill([
                'payment_status' => PaymentStatus::Approved,
                'payment_gateway_id' => $payment->payment_gateway_id,
                'transaction_id' => $payment->transaction_id,
                'payment_proof' => $payment->proof_path,
                'paid_at' => now(),
                'completed_at' => now(),
            ]);
            $this->orders->transition($order, OrderStatus::Completed, $admin, 'Payment approved - order completed');
            $this->orders->grantDownloadPermissions($order);
            $this->orders->incrementSales($order);
        });

        $this->notifications->send($order->user, new PaymentApproved($payment));
        $this->notifications->send($order->user, new OrderCompleted($order));
        $this->logger->log('payment.approved', $payment, "Payment #{$payment->getKey()} for order {$order->order_number} approved", [
            'amount' => $payment->amount,
        ], $admin);

        return $payment;
    }

    public function reject(Payment $payment, User $admin, string $reason): Payment
    {
        if (! $payment->isReviewable()) {
            throw ValidationException::withMessages(['payment' => 'This payment has already been reviewed.']);
        }

        $order = $payment->order;

        DB::transaction(function () use ($payment, $order, $admin, $reason) {
            $from = $payment->status;
            $payment->forceFill([
                'status' => PaymentRecordStatus::Rejected,
                'rejection_reason' => Str::limit($reason, 1000, ''),
                'reviewed_by' => $admin->getKey(),
                'reviewed_at' => now(),
            ])->save();
            $this->recordHistory($payment, $from, PaymentRecordStatus::Rejected, $admin, $reason);

            $order->forceFill(['payment_status' => PaymentStatus::Rejected]);
            $this->orders->transition($order, OrderStatus::AwaitingPayment, $admin, 'Payment rejected: '.$reason);
        });

        $this->notifications->send($order->user, new PaymentRejected($payment));
        $this->logger->log('payment.rejected', $payment, "Payment #{$payment->getKey()} for order {$order->order_number} rejected", ['reason' => $reason], $admin);

        return $payment;
    }

    protected function recordHistory(Payment $payment, ?PaymentRecordStatus $from, PaymentRecordStatus $to, ?User $actor, ?string $note = null): void
    {
        $payment->statusHistories()->create([
            'user_id' => $actor?->getKey(),
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'note' => $note,
        ]);
    }
}
