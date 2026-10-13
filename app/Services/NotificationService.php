<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Throwable;

/**
 * Sends notifications without letting delivery failures (e.g. SMTP errors)
 * break the business operation that triggered them.
 */
class NotificationService
{
    /**
     * @param  User|iterable<User>|null  $notifiables
     */
    public function send(mixed $notifiables, Notification $notification): void
    {
        if (empty($notifiables) || ($notifiables instanceof Collection && $notifiables->isEmpty())) {
            return;
        }

        try {
            NotificationFacade::send($notifiables, $notification);
        } catch (Throwable $e) {
            Log::error('Notification delivery failed', [
                'notification' => $notification::class,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Active staff accounts that hold the given permission.
     *
     * @return Collection<int, User>
     */
    public function staffWithPermission(string $permission): Collection
    {
        return User::query()
            ->where('status', UserStatus::Active->value)
            ->whereHas('role', fn (Builder $q) => $q->where('slug', RoleName::SuperAdmin->value))
            ->orWhere(function (Builder $q) use ($permission) {
                $q->where('status', UserStatus::Active->value)
                    ->whereHas('role', fn (Builder $r) => $r->where('slug', RoleName::Admin->value))
                    ->whereHas('permissions', fn (Builder $p) => $p->where('slug', $permission));
            })
            ->get();
    }
}
