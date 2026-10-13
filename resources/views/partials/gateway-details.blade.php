{{-- Payment instructions for one gateway. Expects $gateway and optional $amount. --}}
<div class="d-flex align-items-center gap-2 mb-2">
    @if ($gateway->logoUrl())<img src="{{ $gateway->logoUrl() }}" alt="" class="gateway-logo">@endif
    <div>
        <div class="fw-semibold text-ink">{{ $gateway->name }}</div>
        @if ($gateway->description)<div class="small text-muted-2">{{ $gateway->description }}</div>@endif
    </div>
</div>
@if (isset($amount))
    <div class="account-detail"><span class="small text-muted-2">Amount to pay</span><span class="value">{{ money($amount, $gateway->currency) }}</span></div>
@endif
@foreach ($gateway->detailRows() as $row)
    <div class="account-detail">
        <span class="small text-muted-2">{{ $row['label'] }}</span>
        <span class="d-flex align-items-center gap-2 min-w-0">
            <span class="value">{{ $row['value'] }}</span>
            <button type="button" class="btn btn-sm btn-light btn-icon" data-copy="{{ $row['value'] }}" aria-label="Copy {{ $row['label'] }}"><i class="bi bi-clipboard"></i></button>
        </span>
    </div>
@endforeach
@if ($gateway->instructions)
    <div class="small mt-2" style="white-space: pre-line">{{ $gateway->instructions }}</div>
@endif
