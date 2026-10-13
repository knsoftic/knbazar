@extends('layouts.admin')

@section('title', 'Reports')

@php $query = array_filter(['type' => $type, 'range' => $range->key, 'from' => request('from'), 'to' => request('to')]); @endphp

@section('content')
    <div class="panel-title">
        <div><h1>{{ $report['title'] }}</h1><p>{{ $range->label }} &middot; {{ $range->from->format('M j, Y') }} - {{ $range->to->format('M j, Y') }}</p></div>
        <a href="{{ route('admin.reports.export', $query) }}" class="btn btn-outline-primary"><i class="bi bi-filetype-csv"></i> Export CSV</a>
    </div>

    <ul class="nav nav-pills gap-1 mb-3 flex-wrap">
        @foreach (\App\Services\ReportService::TYPES as $key => $label)
            <li class="nav-item"><a class="nav-link {{ $type === $key ? 'active' : '' }}" href="{{ route('admin.reports.index', array_merge($query, ['type' => $key])) }}">{{ str_replace(' report', '', $label) }}</a></li>
        @endforeach
    </ul>

    <form method="GET" class="card mb-4">
        <div class="card-body row g-2 align-items-end">
            <input type="hidden" name="type" value="{{ $type }}">
            <div class="col-md-3">
                <label class="form-label small" for="range">Period</label>
                <select name="range" id="range" class="form-select" data-range-select>
                    @foreach (\App\Support\DateRange::PRESETS as $key => $label)<option value="{{ $key }}" @selected($range->key === $key)>{{ $label }}</option>@endforeach
                </select>
            </div>
            <div class="col-6 col-md-3" data-range-custom><label class="form-label small" for="from">From</label><input type="date" name="from" id="from" value="{{ $range->from->format('Y-m-d') }}" class="form-control"></div>
            <div class="col-6 col-md-3" data-range-custom><label class="form-label small" for="to">To</label><input type="date" name="to" id="to" value="{{ $range->to->format('Y-m-d') }}" class="form-control"></div>
            <div class="col-md-2 d-grid"><button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Apply</button></div>
        </div>
    </form>

    <div class="row g-3 mb-4">
        @foreach ($report['summary'] as $label => $value)
            <div class="col-6 col-md-4 col-xl-3"><x-stat-card :label="$label" :value="is_numeric($value) ? number_format($value) : $value" icon="bi-bar-chart" /></div>
        @endforeach
    </div>

    @if ($report['chart'])
        <div class="card mb-4">
            <div class="card-header">{{ $report['chart']['label'] }} per day</div>
            <div class="card-body"><div class="chart-box"><canvas data-chart="{{ json_encode(['labels' => $report['chart']['labels'], 'data' => $report['chart']['data'], 'label' => $report['chart']['label'], 'type' => count($report['chart']['labels']) > 45 ? 'line' : 'bar']) }}" role="img" aria-label="{{ $report['chart']['label'] }} chart"></canvas></div></div>
        </div>
    @endif

    <div class="card">
        <div class="card-header">Details ({{ count($report['rows']) }} rows)</div>
        @if (empty($report['rows']))
            <x-empty-state icon="bi-table" title="No data for this period" />
        @else
            <div class="table-responsive-md">
                <table class="table table-hover table-stack">
                    <thead><tr>@foreach ($report['columns'] as $label)<th>{{ $label }}</th>@endforeach</tr></thead>
                    <tbody>
                        @foreach ($report['rows'] as $row)
                            <tr>@foreach (array_keys($report['columns']) as $key)<td>{{ $row[$key] ?? '' }}</td>@endforeach</tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
@endpush
