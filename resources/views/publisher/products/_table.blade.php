<div class="table-responsive-md">
    <table class="table table-hover table-stack">
        <thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Status</th><th>Approval</th><th>Downloads</th><th>Created</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
            @foreach ($products as $product)
                <tr>
                    <td>
                        <div class="d-flex gap-2 align-items-center">
                            <img src="{{ $product->thumbnailUrl() }}" class="thumb" alt="" loading="lazy">
                            <a href="{{ route('publisher.products.show', $product) }}" class="fw-semibold text-ink text-truncate d-inline-block" style="max-width: 220px">{{ $product->name }}</a>
                        </div>
                    </td>
                    <td>{{ $product->category?->name }}</td>
                    <td><x-price :product="$product" /></td>
                    <td><x-status-badge :status="$product->status" /></td>
                    <td>
                        <x-status-badge :status="$product->approval_status" />
                        @if ($product->isRejected() && $product->rejection_reason)
                            <i class="bi bi-info-circle text-danger" data-bs-toggle="tooltip" title="{{ $product->rejection_reason }}"></i>
                        @endif
                    </td>
                    <td>{{ number_format($product->download_count) }}</td>
                    <td>{{ $product->created_at->format('M j, Y') }}</td>
                    <td class="text-end td-actions">
                        <div class="d-inline-flex gap-1">
                            <a href="{{ route('publisher.products.show', $product) }}" class="btn btn-sm btn-light" title="View"><i class="bi bi-eye"></i></a>
                            @can('update', $product)
                                <a href="{{ route('publisher.products.edit', $product) }}" class="btn btn-sm btn-light" title="Edit"><i class="bi bi-pencil"></i></a>
                            @endcan
                            @can('submit', $product)
                                <form method="POST" action="{{ route('publisher.products.submit', $product) }}" data-confirm="Submit &quot;{{ $product->name }}&quot; for review?" data-confirm-class="btn-primary" data-confirm-button="Submit">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-soft" title="{{ $product->isRejected() ? 'Resubmit' : 'Submit for review' }}"><i class="bi bi-send"></i> {{ $product->isRejected() ? 'Resubmit' : 'Submit' }}</button>
                                </form>
                            @endcan
                            @can('delete', $product)
                                <form method="POST" action="{{ route('publisher.products.destroy', $product) }}" data-confirm="Delete &quot;{{ $product->name }}&quot;? This cannot be undone.">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light text-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
