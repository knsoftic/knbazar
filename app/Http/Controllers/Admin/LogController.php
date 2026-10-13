<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

/**
 * Activity log (database) plus the tail of the application log files.
 */
class LogController extends Controller
{
    public const FILES = ['marketplace', 'laravel'];

    public function __invoke(Request $request): View
    {
        $tab = $request->query('tab', 'activity');

        $activity = ActivityLog::query()
            ->with('user:id,name,role_id')
            ->when($request->filled('action'), fn ($q) => $q->where('action', 'like', $request->query('action').'%'))
            ->when($request->filled('user'), fn ($q) => $q->where('user_id', (int) $request->query('user')))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        $file = in_array($request->query('file'), self::FILES, true) ? $request->query('file') : 'marketplace';

        return view('admin.logs.index', [
            'tab' => $tab,
            'activity' => $activity,
            'file' => $file,
            'lines' => $tab === 'files' ? $this->tail($file) : [],
            'actions' => ActivityLog::query()->select('action')->distinct()->orderBy('action')->pluck('action'),
        ]);
    }

    /**
     * Last lines of the newest log file for the channel.
     *
     * @return array<int, string>
     */
    protected function tail(string $channel, int $lines = 300): array
    {
        $candidates = collect(File::glob(storage_path("logs/{$channel}*.log")) ?: [])
            ->sortByDesc(fn ($path) => filemtime($path));

        $path = $candidates->first();

        if (! $path || ! is_readable($path)) {
            return [];
        }

        $size = filesize($path);
        $handle = fopen($path, 'rb');
        $chunk = min($size, 256 * 1024);
        fseek($handle, -$chunk, SEEK_END);
        $content = (string) fread($handle, $chunk);
        fclose($handle);

        return array_slice(preg_split('/\r\n|\n/', trim($content)) ?: [], -$lines);
    }
}
