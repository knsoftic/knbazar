<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\ProductStatus;
use App\Models\Keyword;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductSlugRedirect;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Creates and updates products, including slugs, keywords, images and the
 * private downloadable file. Workflow transitions live in ProductApprovalService.
 */
class ProductService
{
    public const MAX_KEYWORDS = 20;

    public function __construct(
        protected SlugService $slugs,
        protected ImageService $images,
        protected FileUploadService $files,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated attributes
     * @param  array<string, mixed>  $uploads  image, gallery[], file, og_image
     */
    public function create(User $owner, array $data, array $uploads = []): Product
    {
        $product = new Product;
        $product->forceFill([
            'user_id' => $owner->getKey(),
            'status' => ProductStatus::Draft,
            'approval_status' => ApprovalStatus::Draft,
        ]);

        return $this->save($product, $data, $uploads);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $uploads
     */
    public function update(Product $product, array $data, array $uploads = []): Product
    {
        return $this->save($product, $data, $uploads);
    }

    public function deleteGalleryImage(ProductImage $image): void
    {
        $this->images->delete($image->path, $image->thumb_path);
        $image->delete();
    }

    /**
     * Soft delete the product. Files are kept so existing buyers' records and
     * a possible restore remain intact.
     */
    public function delete(Product $product): void
    {
        $product->delete();
        CatalogCache::flush();
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $uploads
     */
    protected function save(Product $product, array $data, array $uploads): Product
    {
        $keywords = $data['keywords'] ?? null;
        unset($data['keywords'], $data['image'], $data['gallery'], $data['file'], $data['og_image'], $data['action']);

        $data['slug'] = $this->resolveSlug($product, $data['slug'] ?? null, $data['name'] ?? $product->name);
        $oldSlug = $product->exists ? $product->getOriginal('slug') : null;

        $this->guardGalleryLimit($product, $uploads['gallery'] ?? []);

        // Store new files first; roll them back if the database write fails.
        $stored = $this->storeUploads($uploads);

        try {
            DB::transaction(function () use ($product, $data, $stored, $keywords, $oldSlug) {
                $replaced = [];
                $product->fill($data);

                if (isset($stored['image'])) {
                    $replaced['public'][] = $product->image;
                    $replaced['public'][] = $product->thumbnail;
                    $product->forceFill(['image' => $stored['image']['path'], 'thumbnail' => $stored['image']['thumb']]);
                }

                if (isset($stored['og_image'])) {
                    $replaced['public'][] = $product->og_image;
                    $product->forceFill(['og_image' => $stored['og_image']['path']]);
                }

                if (isset($stored['file'])) {
                    $replaced['private'][] = $product->file_path;
                    $product->forceFill([
                        'file_path' => $stored['file']['path'],
                        'file_name' => $stored['file']['name'],
                        'file_size' => $stored['file']['size'],
                        'file_type' => $stored['file']['type'],
                        'file_mime' => $stored['file']['mime'],
                    ]);
                }

                $product->save();

                if ($oldSlug && $oldSlug !== $product->slug) {
                    $this->recordSlugChange($product, $oldSlug);
                }

                $nextOrder = (int) $product->images()->max('sort_order');
                foreach ($stored['gallery'] ?? [] as $image) {
                    $product->images()->create([
                        'path' => $image['path'],
                        'thumb_path' => $image['thumb'],
                        'alt' => $product->name,
                        'sort_order' => ++$nextOrder,
                    ]);
                }

                if ($keywords !== null) {
                    $this->syncKeywords($product, $keywords);
                }

                DB::afterCommit(function () use ($replaced) {
                    $this->images->delete(...($replaced['public'] ?? []));
                    foreach ($replaced['private'] ?? [] as $path) {
                        $this->files->deletePrivate($path);
                    }
                });
            });
        } catch (Throwable $e) {
            $this->discardStored($stored);

            throw $e;
        }

        CatalogCache::flush();

        return $product->refresh();
    }

    public function resolveSlug(Product $product, ?string $requested, ?string $name): string
    {
        $source = filled($requested) ? $requested : (string) $name;

        // Keep the current slug when nothing about it changed.
        if ($product->exists && Str::slug($source) === $product->slug) {
            return $product->slug;
        }

        return $this->slugs->unique(Product::class, $source, $product->exists ? $product->getKey() : null);
    }

    /**
     * @param  string|array<int, string>  $keywords
     */
    public function syncKeywords(Product $product, string|array $keywords): void
    {
        $names = is_array($keywords) ? $keywords : preg_split('/[,\n]+/', $keywords);

        $ids = collect($names ?: [])
            ->map(fn ($name) => Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $name))), 60, ''))
            ->filter(fn ($name) => mb_strlen($name) >= 2)
            ->unique(fn ($name) => Str::slug($name))
            ->take(self::MAX_KEYWORDS)
            ->map(fn ($name) => Keyword::query()->firstOrCreate(['slug' => Str::slug($name)], ['name' => Str::lower($name)])->getKey())
            ->all();

        $product->keywords()->sync($ids);
    }

    protected function recordSlugChange(Product $product, string $oldSlug): void
    {
        // The new slug must not keep redirecting elsewhere.
        ProductSlugRedirect::query()->where('old_slug', $product->slug)->delete();
        ProductSlugRedirect::query()->updateOrCreate(['old_slug' => $oldSlug], ['product_id' => $product->getKey()]);
    }

    /**
     * @param  array<int, UploadedFile>  $gallery
     */
    protected function guardGalleryLimit(Product $product, array $gallery): void
    {
        $max = (int) setting('upload_gallery_max', 8);
        $existing = $product->exists ? $product->images()->count() : 0;

        if ($gallery && $existing + count($gallery) > $max) {
            throw ValidationException::withMessages([
                'gallery' => "A product can have at most {$max} gallery images ({$existing} already uploaded).",
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $uploads
     * @return array<string, mixed>
     */
    protected function storeUploads(array $uploads): array
    {
        $stored = [];
        $image = config('marketplace.image');

        try {
            if (($uploads['image'] ?? null) instanceof UploadedFile) {
                $stored['image'] = $this->images->store($uploads['image'], 'products/images', null, [$image['thumb_width'], $image['thumb_height']], field: 'image');
            }

            if (($uploads['og_image'] ?? null) instanceof UploadedFile) {
                $stored['og_image'] = $this->images->store($uploads['og_image'], 'products/og', 1200, field: 'og_image');
            }

            foreach ($uploads['gallery'] ?? [] as $file) {
                if ($file instanceof UploadedFile) {
                    $stored['gallery'][] = $this->images->store($file, 'products/gallery', null, [$image['gallery_thumb_width'], $image['gallery_thumb_height']], field: 'gallery');
                }
            }

            if (($uploads['file'] ?? null) instanceof UploadedFile) {
                $stored['file'] = $this->files->storeProductFile($uploads['file']);
            }
        } catch (Throwable $e) {
            $this->discardStored($stored);

            throw $e;
        }

        return $stored;
    }

    /**
     * @param  array<string, mixed>  $stored
     */
    protected function discardStored(array $stored): void
    {
        $this->images->delete(
            $stored['image']['path'] ?? null,
            $stored['image']['thumb'] ?? null,
            $stored['og_image']['path'] ?? null,
            ...collect($stored['gallery'] ?? [])->flatMap(fn ($g) => [$g['path'], $g['thumb']])->all()
        );

        $this->files->deletePrivate($stored['file']['path'] ?? null);
    }
}
