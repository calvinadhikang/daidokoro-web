<?php

namespace Tests\Feature;

use App\Models\MenuModel;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionAdminEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_transaction_header(): void
    {
        $transaction = Transaction::query()->create([
            'customer_name' => 'Alex Tan',
            'customer_phone' => '6281111111111',
            'service_type' => 'dine_in',
            'status' => 'in_progress',
            'total_bill' => 0,
        ]);

        $response = $this->patch(route('admin.transaction.update', $transaction), [
            'customer_name' => 'Budi',
            'customer_phone' => '081234567890',
            'service_type' => 'takeaway',
        ]);

        $response->assertRedirect(route('admin.transaction.show', $transaction));
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'customer_name' => 'Budi',
            'customer_phone' => '6281234567890',
            'service_type' => 'takeaway',
        ]);
    }

    public function test_admin_can_update_and_delete_item(): void
    {
        $menu = MenuModel::query()->create([
            'name' => 'Edamame',
            'price' => 18000,
            'is_available' => true,
        ]);

        $transaction = Transaction::query()->create([
            'customer_name' => 'Alex Tan',
            'customer_phone' => '6281111111111',
            'status' => 'in_progress',
            'total_bill' => 18000,
        ]);

        $item = TransactionItem::query()->create([
            'transaction_id' => $transaction->id,
            'menu_id' => $menu->id,
            'menu_name' => $menu->name,
            'quantity' => 1,
            'unit_price' => 18000,
            'line_total' => 18000,
        ]);

        $this->patch(route('admin.transaction.items.update', [$transaction, $item]), [
            'quantity' => 3,
            'addon_option_ids' => [],
            'note' => 'no salt',
        ])->assertRedirect(route('admin.transaction.show', $transaction));

        $this->assertDatabaseHas('transaction_items', [
            'id' => $item->id,
            'quantity' => 3,
            'line_total' => 54000,
            'note' => 'no salt',
        ]);

        $this->delete(route('admin.transaction.items.destroy', [$transaction, $item]))
            ->assertRedirect(route('admin.transaction.show', $transaction));

        $this->assertDatabaseMissing('transaction_items', ['id' => $item->id]);
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'total_bill' => 0,
        ]);
    }

    public function test_admin_item_from_another_transaction_returns_404(): void
    {
        $menu = MenuModel::query()->create([
            'name' => 'Edamame',
            'price' => 18000,
            'is_available' => true,
        ]);

        $transaction = Transaction::query()->create([
            'customer_name' => 'Alex Tan',
            'customer_phone' => '6281111111111',
            'status' => 'in_progress',
            'total_bill' => 0,
        ]);

        $other = Transaction::query()->create([
            'customer_name' => 'Other',
            'customer_phone' => '6281222222222',
            'status' => 'in_progress',
            'total_bill' => 18000,
        ]);

        $item = TransactionItem::query()->create([
            'transaction_id' => $other->id,
            'menu_id' => $menu->id,
            'menu_name' => $menu->name,
            'quantity' => 1,
            'unit_price' => 18000,
            'line_total' => 18000,
        ]);

        $this->patch(route('admin.transaction.items.update', [$transaction, $item]), [
            'quantity' => 2,
        ])->assertNotFound();

        $this->delete(route('admin.transaction.items.destroy', [$transaction, $item]))
            ->assertNotFound();
    }

    public function test_admin_cannot_edit_paid_transaction(): void
    {
        $menu = MenuModel::query()->create([
            'name' => 'Edamame',
            'price' => 18000,
            'is_available' => true,
        ]);

        $transaction = Transaction::query()->create([
            'customer_name' => 'Alex Tan',
            'customer_phone' => '6281111111111',
            'status' => 'paid',
            'total_bill' => 18000,
        ]);

        $item = TransactionItem::query()->create([
            'transaction_id' => $transaction->id,
            'menu_id' => $menu->id,
            'menu_name' => $menu->name,
            'quantity' => 1,
            'unit_price' => 18000,
            'line_total' => 18000,
        ]);

        $this->patch(route('admin.transaction.update', $transaction), [
            'customer_name' => 'Budi',
            'customer_phone' => '081234567890',
            'service_type' => 'takeaway',
        ])->assertSessionHasErrors('transaction');

        $this->patch(route('admin.transaction.items.update', [$transaction, $item]), [
            'quantity' => 2,
        ])->assertSessionHasErrors('transaction');
    }
}
