<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete(); // publisher / owner
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('short_description', 500)->nullable();
            $table->longText('description')->nullable();
            $table->text('features')->nullable();
            $table->text('requirements')->nullable();
            $table->string('product_type', 40)->default('other')->index();

            // Pricing
            $table->boolean('is_free')->default(false)->index();
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->decimal('effective_price', 12, 2)->default(0)->index(); // denormalised final price for filtering/sorting

            // Media & file
            $table->string('image')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('file_path')->nullable();   // private disk path, never public
            $table->string('file_name')->nullable();   // sanitised original name (display only)
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('file_type', 20)->nullable();
            $table->string('file_mime', 120)->nullable();
            $table->string('version', 30)->nullable();
            $table->string('demo_url')->nullable();
            $table->string('documentation_url')->nullable();

            // SEO
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->string('seo_keywords', 500)->nullable();
            $table->string('focus_keyword', 100)->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('og_title')->nullable();
            $table->string('og_description', 500)->nullable();
            $table->string('og_image')->nullable();

            // Flags & workflow
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('whatsapp_enabled')->default(true);
            $table->string('status', 20)->default('draft')->index();
            $table->string('approval_status', 20)->default('draft')->index();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable()->index();

            // Counters
            $table->unsignedBigInteger('view_count')->default(0);
            $table->unsignedBigInteger('download_count')->default(0)->index();
            $table->unsignedBigInteger('sales_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
            $table->index(['category_id', 'status']);
            $table->index(['user_id', 'status']);
        });

        if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            Schema::table('products', function (Blueprint $table) {
                $table->fullText(['name', 'short_description', 'description'], 'products_search_fulltext');
            });
        }

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('thumb_path')->nullable();
            $table->string('alt')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('keywords', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->timestamps();

            $table->index('name');
        });

        Schema::create('product_keywords', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('keyword_id')->constrained()->cascadeOnDelete();
            $table->primary(['product_id', 'keyword_id']);
            $table->index('keyword_id');
        });

        Schema::create('product_slug_redirects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('old_slug')->unique();
            $table->timestamps();
        });

        Schema::create('product_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_status_histories');
        Schema::dropIfExists('product_slug_redirects');
        Schema::dropIfExists('product_keywords');
        Schema::dropIfExists('keywords');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
    }
};
