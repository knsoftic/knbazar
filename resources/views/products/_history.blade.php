{{-- Product status timeline. Expects $product with statusHistories.user loaded. --}}
@if ($product->statusHistories->isEmpty())
    <p class="small text-muted-2 mb-0">No history yet.</p>
@else
    <ul class="timeline">
        @foreach ($product->statusHistories as $history)
            <li>
                <div class="fw-semibold small text-ink">{{ \App\Enums\ProductStatus::tryFrom($history->to_status)?->label() ?? $history->to_status }}</div>
                @if ($history->note)<div class="small">{{ $history->note }}</div>@endif
                <div class="when">{{ $history->created_at?->format('M j, Y H:i') }} @if ($history->user) &middot; {{ $history->user->name }} @endif</div>
            </li>
        @endforeach
    </ul>
@endif
