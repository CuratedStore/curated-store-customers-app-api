<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\CustomersDataStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private readonly CustomersDataStore $store)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        $data = $this->store->read();

        $orders = collect($data['orders'])
            ->where('user_id', (int) $user['id'])
            ->sortByDesc('id')
            ->values();

        return response()->json([
            'message' => 'Orders fetched successfully.',
            'orders' => $orders,
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        $data = $this->store->read();

        $order = collect($data['orders'])->first(static function (array $row) use ($id, $user) {
            return (int) $row['id'] === $id && (int) $row['user_id'] === (int) $user['id'];
        });

        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        return response()->json([
            'message' => 'Order fetched successfully.',
            'order' => $order,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        $data = $this->store->update(function (array $data) use ($user, $request) {
            $uid = (string) $user['id'];
            $cartLines = collect($data['carts'][$uid] ?? [])->values();

            if ($cartLines->isEmpty()) {
                $data['_error'] = 'Cart is empty.';
                return $data;
            }

            $productsById = collect($data['products'])->keyBy('id');
            $items = [];
            $total = 0;

            foreach ($cartLines as $line) {
                $product = $productsById->get((int) $line['product_id']);
                if (!$product) {
                    continue;
                }

                $qty = min((int) $line['quantity'], (int) $product['stock']);
                $price = (float) $product['price'];
                $lineTotal = $qty * $price;
                $total += $lineTotal;

                $items[] = [
                    'product_id' => (int) $product['id'],
                    'name' => (string) $product['name'],
                    'price' => $price,
                    'quantity' => $qty,
                    'line_total' => $lineTotal,
                ];

                foreach ($data['products'] as &$productRow) {
                    if ((int) $productRow['id'] === (int) $product['id']) {
                        $productRow['stock'] = max(0, (int) $productRow['stock'] - $qty);
                        break;
                    }
                }
                unset($productRow);
            }

            $addresses = $user['addresses'] ?? [];
            $shippingAddress = collect($addresses)->firstWhere('is_default', true) ?? ($addresses[0] ?? null);

            $orderId = $this->store->nextId($data['orders']);
            $order = [
                'id' => $orderId,
                'user_id' => (int) $user['id'],
                'status' => 'placed',
                'payment_method' => (string) $request->input('payment_method', 'COD'),
                'shipping_address' => $shippingAddress,
                'total' => $total,
                'items' => $items,
                'events' => [
                    [
                        'title' => 'Order placed',
                        'description' => 'Your order has been placed successfully.',
                        'created_at' => now()->toIso8601String(),
                    ],
                ],
                'created_at' => now()->toIso8601String(),
                'cancel_requested' => false,
                'return_requested' => false,
            ];

            $data['orders'][] = $order;
            $data['carts'][$uid] = [];
            $data['_order_id'] = $orderId;

            return $data;
        });

        if (isset($data['_error'])) {
            return response()->json(['message' => $data['_error']], 422);
        }

        $order = collect($data['orders'])->firstWhere('id', (int) $data['_order_id']);

        return response()->json([
            'message' => 'Order placed successfully.',
            'order' => $order,
        ], 201);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        return $this->markRequestFlag($request, $id, 'cancel_requested', 'Cancel request submitted.');
    }

    public function requestReturn(Request $request, int $id): JsonResponse
    {
        return $this->markRequestFlag($request, $id, 'return_requested', 'Return request submitted.');
    }

    public function invoice(Request $request, int $id): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        $data = $this->store->read();
        $order = collect($data['orders'])->first(static fn (array $row) => (int) $row['id'] === $id && (int) $row['user_id'] === (int) $user['id']);

        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        return response()->json([
            'message' => 'Invoice generated.',
            'invoice_url' => url('/api/api/orders/'.$id.'/invoice'),
            'order' => $order,
        ]);
    }

    private function markRequestFlag(Request $request, int $id, string $flag, string $message): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        $data = $this->store->update(function (array $data) use ($id, $user, $flag, $request) {
            $found = false;
            foreach ($data['orders'] as &$order) {
                if ((int) $order['id'] !== $id || (int) $order['user_id'] !== (int) $user['id']) {
                    continue;
                }

                if (!empty($order[$flag])) {
                    $data['_error'] = 'Request already submitted for this order.';
                    return $data;
                }

                $reason = (string) $request->input('reason', 'No reason provided');
                $order[$flag] = true;
                $order['events'][] = [
                    'title' => $flag === 'cancel_requested' ? 'Cancel requested' : 'Return requested',
                    'description' => $reason,
                    'created_at' => now()->toIso8601String(),
                ];
                $found = true;
                break;
            }
            unset($order);

            if (!$found) {
                $data['_error'] = 'Order not found.';
                return $data;
            }

            return $data;
        });

        if (isset($data['_error'])) {
            $status = $data['_error'] === 'Order not found.' ? 404 : 409;
            return response()->json(['message' => $data['_error']], $status);
        }

        $order = collect($data['orders'])->firstWhere('id', $id);

        return response()->json([
            'message' => $message,
            'order' => $order,
        ]);
    }
}
