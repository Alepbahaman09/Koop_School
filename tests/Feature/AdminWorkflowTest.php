<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_pages_render_with_database_data(): void
    {
        $admin = User::factory()->create();
        $category = Category::create(['name' => 'Stationery', 'is_active' => true]);
        Product::create([
            'category_id' => $category->id,
            'sku' => 'PEN-001',
            'name' => 'Blue Pen',
            'price' => 1.50,
            'stock_quantity' => 20,
            'min_stock_level' => 5,
            'is_active' => true,
        ]);
        Customer::create([
            'student_id' => 'S001',
            'parent_name' => 'Parent One',
            'student_name' => 'Student One',
            'email' => 'parent@example.com',
            'phone' => '0123456789',
            'class' => '5 Amanah',
            'address' => 'School Road',
            'is_active' => true,
        ]);

        $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertSee('Active Products');
        $this->actingAs($admin)->get(route('products.index'))->assertOk()->assertSee('Blue Pen');
        $this->actingAs($admin)->get(route('users.index'))->assertOk()->assertSee('Parent One');
        $this->actingAs($admin)->get(route('orders.index'))->assertOk()->assertSee('No orders match these filters.');
    }

    public function test_product_stock_changes_are_recorded(): void
    {
        $admin = User::factory()->create();
        $category = Category::create(['name' => 'Books', 'is_active' => true]);

        $this->actingAs($admin)->post(route('products.store'), [
            'category_id' => $category->id,
            'sku' => 'BOOK-001',
            'name' => 'Exercise Book',
            'price' => 3.50,
            'stock_quantity' => 30,
            'min_stock_level' => 5,
            'is_active' => 1,
        ])->assertRedirect(route('products.index'));

        $product = Product::where('sku', 'BOOK-001')->firstOrFail();
        $this->assertDatabaseHas('inventory_transactions', [
            'product_id' => $product->id,
            'type' => 'In',
            'stock_before' => 0,
            'stock_after' => 30,
        ]);

        $this->actingAs($admin)->patch(route('products.update', $product), [
            'category_id' => $category->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'price' => $product->price,
            'stock_quantity' => 24,
            'min_stock_level' => 5,
            'is_active' => 1,
        ])->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('inventory_transactions', [
            'product_id' => $product->id,
            'type' => 'Adjustment',
            'stock_before' => 30,
            'stock_after' => 24,
        ]);
    }

    public function test_order_status_can_be_updated_from_admin_page(): void
    {
        $admin = User::factory()->create();
        $customer = Customer::create([
            'student_id' => 'S002',
            'parent_name' => 'Parent Two',
            'student_name' => 'Student Two',
            'email' => 'parent2@example.com',
            'phone' => '0123456788',
            'class' => '4 Bestari',
            'address' => 'School Road',
            'is_active' => true,
        ]);
        $order = Order::create([
            'order_number' => 'KS-TEST-0001',
            'customer_id' => $customer->id,
            'user_id' => $admin->id,
            'status' => 'Pending',
            'subtotal' => 10,
            'total_amount' => 10,
            'payment_status' => 'Unpaid',
        ]);

        $this->actingAs($admin)->patch(route('orders.updateStatus', $order), [
            'status' => 'Processing',
            'notes' => 'Preparing order',
        ])->assertRedirect();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'Processing']);
        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->id,
            'user_id' => $admin->id,
            'status' => 'Processing',
        ]);
    }

    public function test_mobile_order_reduces_product_stock(): void
    {
        $admin = User::factory()->create();
        $category = Category::create(['name' => 'Uniforms', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'SHIRT-001',
            'name' => 'School Shirt',
            'price' => 25,
            'stock_quantity' => 10,
            'min_stock_level' => 2,
            'is_active' => true,
        ]);
        $customer = Customer::create([
            'student_id' => 'S003',
            'parent_name' => 'Parent Three',
            'student_name' => 'Student Three',
            'email' => 'parent3@example.com',
            'phone' => '0123456787',
            'class' => '3 Cekap',
            'address' => 'School Road',
            'is_active' => true,
        ]);

        $this->postJson('/api/v1/orders', [
            'customer_id' => $customer->id,
            'user_id' => $admin->id,
            'items' => [['product_id' => $product->id, 'quantity' => 3]],
        ])->assertCreated();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 7]);
        $this->assertDatabaseHas('inventory_transactions', [
            'product_id' => $product->id,
            'type' => 'Out',
            'stock_before' => 10,
            'stock_after' => 7,
        ]);
    }
}
