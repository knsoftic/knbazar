<?php

namespace Tests\Feature;

use App\Enums\PublisherStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_private_areas(): void
    {
        foreach (['/admin', '/admin/products', '/publisher', '/publisher/products', '/account', '/orders', '/downloads', '/checkout', '/notifications'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_customers_and_publishers_cannot_access_admin(): void
    {
        $this->actingAs(User::factory()->customer()->create())->get('/admin')->assertForbidden();
        $this->actingAs(User::factory()->publisher()->create())->get('/admin/products')->assertForbidden();
    }

    public function test_customers_and_admins_cannot_access_publisher_area(): void
    {
        $this->actingAs(User::factory()->customer()->create())->get('/publisher')->assertForbidden();
        $this->actingAs($this->superAdmin())->get('/publisher/products')->assertForbidden();
    }

    public function test_super_admin_can_access_everything(): void
    {
        $admin = $this->superAdmin();

        foreach (['/admin', '/admin/products', '/admin/orders', '/admin/payments', '/admin/settings', '/admin/seo', '/admin/admins', '/admin/logs', '/admin/reports'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_admin_permissions_are_enforced(): void
    {
        $admin = User::factory()->admin(['manage_orders'])->create();

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->get('/admin/orders')->assertOk();

        foreach (['/admin/payments', '/admin/products', '/admin/settings/general', '/admin/seo', '/admin/admins', '/admin/users', '/admin/logs', '/admin/categories', '/admin/payment-gateways'] as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_reviewer_can_view_and_approve_but_not_edit_products(): void
    {
        $reviewer = User::factory()->admin(['approve_products'])->create();
        $product = Product::factory()->pending()->create();

        $this->actingAs($reviewer)->get('/admin/products')->assertOk();
        $this->get(route('admin.products.show', $product))->assertOk();
        $this->get(route('admin.products.edit', $product))->assertForbidden();
        $this->post(route('admin.products.approve', $product))->assertSessionHas('success');
        $this->delete(route('admin.products.destroy', $product))->assertForbidden();
    }

    public function test_admin_cannot_approve_payments_without_permission(): void
    {
        $admin = User::factory()->admin(['manage_orders'])->create();
        $payment = Payment::query()->forceCreate([
            'order_id' => Order::query()->forceCreate([
                'order_number' => 'ORD-X', 'customer_name' => 'A', 'customer_email' => 'a@example.com',
                'total' => 10, 'currency' => 'PKR', 'payment_status' => 'submitted', 'status' => 'payment_submitted',
            ])->id,
            'amount' => 10, 'currency' => 'PKR', 'transaction_id' => 'T1', 'status' => 'under_review',
        ]);

        $this->actingAs($admin)->post(route('admin.payments.approve', $payment))->assertForbidden();
        $this->assertSame('under_review', $payment->fresh()->status->value);
    }

    public function test_only_super_admins_manage_admin_accounts(): void
    {
        $admin = User::factory()->admin(['manage_users'])->create();
        $this->actingAs($admin)->get('/admin/admins')->assertForbidden();

        $super = $this->superAdmin();
        $this->actingAs($super)->post('/admin/admins', [
            'name' => 'New Admin', 'username' => 'newadmin', 'email' => 'newadmin@example.com',
            'password' => 'secret123', 'password_confirmation' => 'secret123',
            'role' => 'admin', 'status' => 'active', 'permissions' => ['manage_orders', 'view_reports'],
        ])->assertRedirect(route('admin.admins.index'));

        $created = User::query()->where('email', 'newadmin@example.com')->firstOrFail();
        $this->assertTrue($created->isAdmin());
        $this->assertTrue($created->hasPermission('view_reports'));
        $this->assertFalse($created->hasPermission('manage_payments'));
    }

    public function test_last_super_admin_cannot_be_demoted(): void
    {
        $super = $this->superAdmin();

        $this->actingAs($super)->put(route('admin.admins.update', $super), [
            'name' => $super->name, 'username' => $super->username, 'email' => $super->email,
            'role' => 'admin', 'status' => 'active',
        ])->assertSessionHas('error');

        $this->assertTrue($super->fresh()->isSuperAdmin());
    }

    public function test_manage_users_cannot_escalate_to_admin(): void
    {
        $admin = User::factory()->admin(['manage_users'])->create();
        $customer = User::factory()->customer()->create();

        $this->actingAs($admin)->put(route('admin.users.update', $customer), [
            'name' => $customer->name, 'username' => $customer->username, 'email' => $customer->email,
            'role' => 'super_admin', 'status' => 'active',
        ])->assertSessionHasErrors('role');

        $this->assertTrue($customer->fresh()->isCustomer());
    }

    public function test_publisher_approval_by_admin(): void
    {
        $publisher = User::factory()->publisher(PublisherStatus::Pending)->create();
        $admin = User::factory()->admin(['manage_publishers'])->create();

        $this->actingAs($admin)->post(route('admin.publishers.status', $publisher), ['status' => 'rejected'])
            ->assertSessionHasErrors('reason');

        $this->post(route('admin.publishers.status', $publisher), ['status' => 'approved'])->assertSessionHas('success');
        $this->assertTrue($publisher->fresh()->canSubmitProducts());
    }

    public function test_security_headers_are_sent(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}
