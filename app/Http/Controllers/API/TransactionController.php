<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionRequest;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionPaymentMethod;
use App\Models\TransactionProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $customer_id = auth()->user()->customer->id;

            $transactions = Transaction::with('products')
                ->where('customer_id', $customer_id)
                ->get();

            return response()->json([
                'message' => 'Success get transactions',
                'data' => $transactions,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function store(TransactionRequest $request)
    {
        try {
            DB::beginTransaction();

            $products = Product::whereIn('id', array_column($request->products, 'id'))
                ->select('id', 'price')
                ->get();

            $total_price_array = array_map(function ($product) use ($request) {
                $product['qty'] = $request->products[
                    array_search($product['id'], 
                    array_column($request->products, 'id'))
                ]['qty'];

                return $product['price'] * $product['qty'];
            }, $products->toArray());

            $total_price = array_sum($total_price_array);

            $transaction = Transaction::create([
                'customer_id' => auth()->user()->customer->id,
                'customer_address_id' => $request->address_id,
                'total_price' => $total_price,
                'date' => now()->format('Y-m-d'),
                'times' => now()->format('H:i:s'),
            ]);

            foreach ($request->products as $key => $value) {
                TransactionProduct::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $value['id'],
                    'qty' => $value['qty'],
                ]);
            }

            foreach ($request->payment_method as $key => $value) {
                $payment_method_status = PaymentMethod::find($value)->is_active;
                if (!$payment_method_status) {
                    throw new \Exception('Payment method is not active', 400);
                }

                TransactionPaymentMethod::create([
                    'transaction_id' => $transaction->id,
                    'payment_method_id' => $value,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Success create transaction',
                'data' => $transaction,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $customer_id = auth()->user()->customer->id;

            $transactions = Transaction::with('products')
                ->where('customer_id', $customer_id)
                ->get();

            return response()->json([
                'message' => 'Success get transaction details',
                'data' => $transactions,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }
}
