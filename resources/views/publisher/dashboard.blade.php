@extends('layouts.publisher')

@section('title', 'Publisher dashboard')

@section('content')
    <div class="panel-title">
        <div><h1>Welcome, {{ $profile?->display_name ?? $user->name }}</h1><p>Manage your products and track performance.</p></div>
        @if ($user->canSubmitProducts())
            <a href="{{ route('publisher.products.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add product</a>
        @endif
    </div>

    @if (! $profile)
        <div class="alert alert-warning">Your publisher profile is missing. <a href="{{ route('publisher.profile') }}">Complete your profile</a>.</div>
    @elseif ($profile->status === \App\Enums\PublisherStatus::Pending)
        <div class="alert alert-warning d-flex gap-2"><i class="bi bi-hourglass-split mt-1"></i><div><strong>Your publisher account is awaiting approval.</strong> You can complete your profile now; product submission unlocks once our team approves your account.</div></div>
    @elseif ($profile->status === \App\Enums\PublisherStatus::Rejected)
        <div class="alert alert-danger"><strong>Your publisher application was rejected.</strong> @if ($profile->rejection_reason)Reason: {{ $profile->rejection_reason }}@endif <a href="{{ route('contact.show') }}">Contact us</a> if you have questions.</div>
    @elseif ($profile->status === \App\Enums\PublisherStatus::Suspended)
        <div class="alert alert-danger"><strong>Your publisher account is suspended.</strong> Please <a href="{{ route('contact.show') }}">contact support</a>.</div>
    @endif

    @if ($profile && $profile->completeness() < 100)
        <div class="card mb-4">
            <div class="card-body d-flex flex-wrap align-items-center gap-3">
                <div class="flex-grow-1">
                    <div class="fw-semibold text-ink mb-1">Profile {{ $profile->completeness() }}% complete</div>
                    <div class="progress" style="height: 8px" role="progressbar" aria-valuenow="{{ $profile->completeness() }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width: {{ $profile->completeness() }}%"></div></div>
                </div>
                <a href="{{ route('publisher.profile') }}" class="btn btn-sm btn-outline-primary">Complete profile</a>
            </div>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-2"><x-stat-card label="Total products" :value="$stats['products']" icon="bi-box-seam" :href="route('publisher.products.index')" /></div>
        <div class="col-6 col-xl-2"><x-stat-card label="Published" :value="$stats['published']" icon="bi-check2-circle" color="success" :href="route('publisher.products.index', ['status' => 'approved'])" /></div>
        <div class="col-6 col-xl-2"><x-stat-card label="Pending review" :value="$stats['pending']" icon="bi-hourglass-split" color="warning" :href="route('publisher.products.index', ['status' => 'pending'])" /></div>
        <div class="col-6 col-xl-2"><x-stat-card label="Rejected" :value="$stats['rejected']" icon="bi-x-octagon" color="danger" :href="route('publisher.products.index', ['status' => 'rejected'])" /></div>
        <div class="col-6 col-xl-2"><x-stat-card label="Total sales" :value="$stats['sales'].' / '.money($stats['revenue'])" icon="bi-cash-coin" color="info" :href="route('publisher.orders')" /></div>
        <div class="col-6 col-xl-2"><x-stat-card label="Total downloads" :value="number_format($stats['downloads'])" icon="bi-download" color="dark" /></div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header">Sales - last 30 days</div>
                @php $salesChart = ['labels' => $charts['labels'], 'data' => $charts['sales'], 'label' => 'Sales', 'color' => '#16a34a']; @endphp
                <div class="card-body"><div class="chart-box sm"><canvas data-chart="{{ json_encode($salesChart) }}" aria-label="Sales chart" role="img"></canvas></div></div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header">Downloads - last 30 days</div>
                @php $downloadsChart = ['labels' => $charts['labels'], 'data' => $charts['downloads'], 'label' => 'Downloads', 'type' => 'bar']; @endphp
                <div class="card-body"><div class="chart-box sm"><canvas data-chart="{{ json_encode($downloadsChart) }}" aria-label="Downloads chart" role="img"></canvas></div></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">Recently updated products <a href="{{ route('publisher.products.index') }}" class="small">View all</a></div>
        @if ($recentProducts->isEmpty())
            <x-empty-state icon="bi-box-seam" title="No products yet" text="Upload your first product and submit it for review.">
                @if ($user->canSubmitProducts())<a href="{{ route('publisher.products.create') }}" class="btn btn-primary btn-sm">Add product</a>@endif
            </x-empty-state>
        @else
            @include('publisher.products._table', ['products' => $recentProducts])
        @endif
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
@endpush
