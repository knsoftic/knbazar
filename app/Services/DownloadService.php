<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\DownloadDeniedException;
use App\Models\Download;
use App\Models\DownloadPermission;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Authorises, tracks and streams product downloads from the private disk.
 * Files are never exposed through public URLs.
 */
class DownloadService
{
    public function __construct(protected ActivityLogger $logger) {}

    /**
     * @throws DownloadDeniedException
     */
    public function download(Product $product, ?User $user): StreamedResponse
    {
        $permission = $this->authorize($product, $user);
        $path = $product->file_path;
        $disk = Storage::disk(FileUploadService::PRIVATE_DISK);

        if (! $path || ! $disk->exists($path)) {
            Log::channel('marketplace')->error('[download] Product file missing', ['product_id' => $product->getKey(), 'path' => $path]);

            throw new DownloadDeniedException('The file for this product is temporarily unavailable. Please contact support.');
        }

        $this->track($product, $user, $permission);

        return $disk->download($path, $this->downloadName($product), [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    /**
     * Runs every download check and returns the permission used (paid) or null (free).
     *
     * @throws DownloadDeniedException
     */
    public function authorize(Product $product, ?User $user): ?DownloadPermission
    {
        if ($product->trashed()) {
            throw new DownloadDeniedException('This product is no longer available.');
        }

        if ($user && ! $user->isActive()) {
            throw new DownloadDeniedException('Your account is suspended.');
        }

        if ($product->is_free) {
            if (! setting('free_downloads_enabled')) {
                throw new DownloadDeniedException('Free downloads are currently disabled.');
            }

            if (! Product::query()->visible()->whereKey($product->getKey())->exists()) {
                throw new DownloadDeniedException('This product is not available.');
            }

            if (! $user && setting('free_download_require_login')) {
                throw new DownloadDeniedException('Please sign in to download free products.', requiresLogin: true);
            }

            return null;
        }

        if (! $user) {
            throw new DownloadDeniedException('Please sign in to download your purchased products.', requiresLogin: true);
        }

        $permissions = $this->permissionsQuery($user, $product)->get();
        $usable = $permissions->first(fn (DownloadPermission $p) => $p->isUsable());

        if ($usable) {
            return $usable;
        }

        $this->logger->security('Download denied', ['product_id' => $product->getKey()]);

        if ($permissions->isNotEmpty()) {
            throw new DownloadDeniedException($permissions->contains(fn (DownloadPermission $p) => $p->isLimitReached())
                ? 'You have reached the download limit for this product.'
                : 'Your download access for this product has expired.');
        }

        throw new DownloadDeniedException('You need to purchase this product, and the payment must be approved, before downloading.');
    }

    /**
     * The best usable permission for a user and product, if any.
     */
    public function usablePermission(User $user, Product $product): ?DownloadPermission
    {
        return $this->permissionsQuery($user, $product)->get()->first(fn (DownloadPermission $p) => $p->isUsable());
    }

    /**
     * Permissions backed by a paid order in a downloadable state.
     */
    public function permissionsQuery(User $user, Product $product): Builder
    {
        return DownloadPermission::query()
            ->where('user_id', $user->getKey())
            ->where('product_id', $product->getKey())
            ->whereHas('order', fn (Builder $q) => $q
                ->where('payment_status', PaymentStatus::Approved->value)
                ->whereIn('status', array_map(fn (OrderStatus $s) => $s->value, OrderStatus::downloadable())))
            ->latest('id');
    }

    public function downloadName(Product $product): string
    {
        $extension = $product->file_type ?: pathinfo((string) $product->file_path, PATHINFO_EXTENSION);
        $version = $product->version ? '-v'.trim(preg_replace('/[^A-Za-z0-9.\-]+/', '-', $product->version), '-.') : '';

        return $product->slug.$version.($extension ? '.'.$extension : '');
    }

    protected function track(Product $product, ?User $user, ?DownloadPermission $permission): void
    {
        $request = request();

        DB::transaction(function () use ($product, $user, $permission, $request) {
            Download::query()->create([
                'user_id' => $user?->getKey(),
                'product_id' => $product->getKey(),
                'order_id' => $permission?->order_id,
                'download_permission_id' => $permission?->getKey(),
                'is_free' => $product->is_free,
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 495, ''),
            ]);

            // Query builder increments do not touch updated_at.
            DB::table('products')->where('id', $product->getKey())->increment('download_count');

            if ($permission) {
                DB::table('download_permissions')->where('id', $permission->getKey())->increment('downloads_count');
            }
        });
    }
}
