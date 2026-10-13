<?php

namespace Tests;

use App\Models\Category;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\SettingService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    /**
     * Roles and permissions are reference data every test needs.
     */
    protected $seed = true;

    protected $seeder = RolePermissionSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        Role::flushIdCache();
        app(SettingService::class)->flush();
    }

    /**
     * @param  array<string, mixed>  $values
     */
    protected function setting(array $values): void
    {
        app(SettingService::class)->setMany($values);
    }

    /**
     * A published product whose downloadable file exists on the fake private disk.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function publishedProduct(array $attributes = [], bool $free = false): Product
    {
        Storage::fake('local');
        Storage::fake('public');

        $factory = Product::factory()->published();
        $product = ($free ? $factory->free() : $factory)->create($attributes);
        Storage::disk('local')->put($product->file_path, 'PK-demo-content');

        return $product;
    }

    protected function gateway(bool $requiresProof = false): PaymentGateway
    {
        return PaymentGateway::factory()->state(['requires_proof' => $requiresProof])->create();
    }

    protected function category(): Category
    {
        return Category::factory()->create();
    }

    protected function superAdmin(): User
    {
        return User::factory()->superAdmin()->create();
    }
}
