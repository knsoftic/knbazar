<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Support\DateRange;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request, ReportService $reports): View
    {
        $type = $this->type($request);
        $range = DateRange::fromRequest($request);

        return view('admin.reports.index', [
            'type' => $type,
            'range' => $range,
            'report' => $reports->build($type, $range),
        ]);
    }

    public function export(Request $request, ReportService $reports): StreamedResponse
    {
        $type = $this->type($request);
        $range = DateRange::fromRequest($request);
        $report = $reports->build($type, $range);
        $filename = Str::slug($report['title']).'-'.$range->from->format('Ymd').'-'.$range->to->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
            fputcsv($out, array_values($report['columns']), escape: '\\');

            foreach ($report['rows'] as $row) {
                fputcsv($out, array_map(
                    fn ($key) => $this->csvSafe($row[$key] ?? ''),
                    array_keys($report['columns'])
                ), escape: '\\');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function type(Request $request): string
    {
        $type = (string) $request->query('type', 'sales');

        return array_key_exists($type, ReportService::TYPES) ? $type : 'sales';
    }

    /**
     * Prevent CSV formula injection when the file is opened in a spreadsheet.
     */
    protected function csvSafe(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
