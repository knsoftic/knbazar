<?php

namespace App\Http\Controllers\Publisher;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Services\SeoService;
use App\Support\DateRange;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ReportService $reports, SeoService $seo): View
    {
        $user = $request->user()->load('publisherProfile');

        return view('publisher.dashboard', [
            'user' => $user,
            'profile' => $user->publisherProfile,
            'stats' => $reports->publisherStats($user),
            'charts' => $reports->publisherCharts($user, DateRange::preset('last_30_days')),
            'recentProducts' => $user->products()->with('category:id,name')->latest('updated_at')->limit(5)->get(),
            'seo' => $seo->defaults('Publisher dashboard')->noindex(),
        ]);
    }
}
