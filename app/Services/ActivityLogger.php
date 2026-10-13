<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Records important admin / security events in the activity_logs table and
 * the dedicated "marketplace" log channel. Never pass secrets in $properties.
 */
class ActivityLogger
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(string $action, ?Model $subject = null, string $description = '', array $properties = [], ?User $user = null, string $level = 'info'): void
    {
        $user ??= auth()->user();
        $request = app()->runningInConsole() && ! app()->runningUnitTests() ? null : request();

        try {
            ActivityLog::query()->create([
                'user_id' => $user?->getKey(),
                'action' => $action,
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'description' => Str::limit($description ?: $action, 495),
                'properties' => $properties ?: null,
                'ip_address' => $request?->ip(),
                'user_agent' => Str::limit((string) $request?->userAgent(), 495, ''),
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to write activity log', ['action' => $action, 'error' => $e->getMessage()]);
        }

        Log::channel('marketplace')->log($level, '['.$action.'] '.$description, array_filter([
            'user_id' => $user?->getKey(),
            'subject' => $subject ? class_basename($subject).'#'.$subject->getKey() : null,
            'ip' => $request?->ip(),
            'properties' => $properties ?: null,
        ]));
    }

    /**
     * Security-sensitive events (failed logins, denied downloads, ...).
     *
     * @param  array<string, mixed>  $context
     */
    public function security(string $event, array $context = []): void
    {
        $request = request();

        Log::channel('marketplace')->warning('[security] '.$event, $context + [
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
        ]);
    }
}
