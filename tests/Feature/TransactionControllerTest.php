<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionPaymentMethod;
use App\Models\TransactionProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the index method of TransactionController.
     *
     * @return void
     */
    public function testIndex()
    {
        // Create a customer
        $customer = Customer::factory()->create();

        // create a customer address
        $customer_address = CustomerAddress::create([
            'customer_id' => $customer->id,
            'address' => "Indonesia",
        ]);

        // Create products
        $products = Product::factory()->count(2)->create();

        // Create transactions for the customer
        $transactions = Transaction::factory()->count(3)->create([
            'customer_id' => $customer->id,
            'customer_address_id' => $customer_address->id,
            'date' => now()->format('Y-m-d'),
            'times' => now()->format('H:i:s'),
            'total_price' => $products->sum('price'),
        ]);

        // Create products for each transaction
        foreach ($transactions as $transaction) {
            foreach ($products as $product) {
                TransactionProduct::factory()->create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $product->id,
                    'qty' => 1,
                ]);
            }
        }

        // Make a request to the index method
        $response = $this->actingAs($customer->user)
            ->get('/api/transactions');
        dd($response);
        // Assert the response
        $response->assertStatus(200);
    }

    /**
     * Test the store method of TransactionController.
     *
     * @return void
     */
    public function testStore()
    {
        // Create a customer
        $customer = Customer::factory()->create();

        // Create products
        $products = Product::factory()->count(2)->create();

        // Create payment method
        $payment_methods = PaymentMethod::factory()->count(2)->create();

        // Prepare the request data
        $requestData = [
            'address_id' => 1,
            'products' => [
                [
                    'id' => $products[0]->id,
                    'qty' => 2,
                ],
                [
                    'id' => $products[1]->id,
                    'qty' => 3,
                ],
            ],
            'payment_method' => $payment_methods->pluck('id')->toArray(),
        ];

        // Make a request to the store method
        $response = $this->actingAs($customer->user)
            ->post('/api/transactions', $requestData);

        // Assert the response
        $response->assertStatus(200);

        // Assert the transaction and related models are created in the database
        $this->assertDatabaseHas('transactions', [
            'customer_id' => $customer->id,
            'address_id' => $requestData['address_id']
        ]);
    }

    /**
     * Test the show method of TransactionController.
     *
     * @return void
     */
    public function testShow()
    {
        // Create a customer
        $customer = Customer::factory()->create();

        // create a customer address
        $customer_address = CustomerAddress::create([
            'customer_id' => $customer->id,
            'address' => "Indonesia",
        ]);

        // Create products
        $products = Product::factory()->count(2)->create();

        // Create transactions for the customer
        $transaction = Transaction::factory()->create([
            'customer_id' => $customer->id,
            'customer_address_id' => $customer_address->id,
            'date' => now()->format('Y-m-d'),
            'times' => now()->format('H:i:s'),
            'total_price' => $products->sum('price'),
        ]);

        foreach ($products as $product) {
            TransactionProduct::factory()->create([
                'transaction_id' => $transaction->id,
                'product_id' => $product->id,
                'qty' => 1,
            ]);
        }

        // Make a request to the show method
        $response = $this->actingAs($customer->user)
            ->get('/api/transactions/' . $transaction->id);

        // Assert the response
        $response->assertStatus(200);
    }
}
