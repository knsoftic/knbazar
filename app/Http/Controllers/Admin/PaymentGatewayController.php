<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaymentGatewayRequest;
use App\Models\PaymentGateway;
use App\Payments\PaymentGatewayManager;
use App\Services\ActivityLogger;
use App\Services\ImageService;
use App\Services\SlugService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PaymentGatewayController extends Controller
{
    public function __construct(
        protected ImageService $images,
        protected SlugService $slugs,
        protected ActivityLogger $logger,
    ) {}

    public function index(): View
    {
        return view('admin.gateways.index', [
            'gateways' => PaymentGateway::query()->withCount('payments')->ordered()->get(),
        ]);
    }

    public function create(PaymentGatewayManager $manager): View
    {
        return view('admin.gateways.form', [
            'gateway' => new PaymentGateway([
                'driver' => 'manual',
                'currency' => currency_code(),
                'requires_proof' => true,
                'is_active' => true,
                'details' => [['label' => 'Account Title', 'value' => ''], ['label' => 'Account Number', 'value' => '']],
            ]),
            'drivers' => $manager->options(),
        ]);
    }

    public function store(PaymentGatewayRequest $request): RedirectResponse
    {
        $gateway = new PaymentGateway($request->safe()->except(['logo', 'remove_logo', 'details']));
        $gateway->details = $request->detailRows();
        $gateway->slug = $this->slugs->unique(PaymentGateway::class, $request->validated('name'));
        $gateway->sort_order = (int) $request->validated('sort_order', 0);

        if ($request->hasFile('logo')) {
            $gateway->logo = $this->images->store($request->file('logo'), 'gateways', 400)['path'];
        }

        $gateway->save();
        $this->logger->log('gateway.created', $gateway, "Payment gateway \"{$gateway->name}\" created");

        return redirect()->route('admin.gateways.index')->with('success', 'Payment gateway created.');
    }

    public function edit(PaymentGateway $gateway, PaymentGatewayManager $manager): View
    {
        return view('admin.gateways.form', ['gateway' => $gateway, 'drivers' => $manager->options()]);
    }

    public function update(PaymentGatewayRequest $request, PaymentGateway $gateway): RedirectResponse
    {
        $gateway->fill($request->safe()->except(['logo', 'remove_logo', 'details']));
        $gateway->details = $request->detailRows();
        $gateway->sort_order = (int) $request->validated('sort_order', 0);

        if ($request->hasFile('logo')) {
            $this->images->delete($gateway->logo);
            $gateway->logo = $this->images->store($request->file('logo'), 'gateways', 400)['path'];
        } elseif ($request->boolean('remove_logo') && $gateway->logo) {
            $this->images->delete($gateway->logo);
            $gateway->logo = null;
        }

        $gateway->save();
        // Account numbers are payment configuration; only record that they changed.
        $this->logger->log('gateway.updated', $gateway, "Payment gateway \"{$gateway->name}\" updated");

        return redirect()->route('admin.gateways.index')->with('success', 'Payment gateway updated.');
    }

    public function destroy(PaymentGateway $gateway): RedirectResponse
    {
        // Soft delete keeps historic orders/payments readable.
        $gateway->delete();
        $this->logger->log('gateway.deleted', $gateway, "Payment gateway \"{$gateway->name}\" deleted");

        return back()->with('success', 'Payment gateway deleted.');
    }
}
