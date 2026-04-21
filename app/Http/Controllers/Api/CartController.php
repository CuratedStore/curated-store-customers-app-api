<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\CustomersDataStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    public function __construct(private readonly CustomersDataStore $store)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $data = $this->store->read();
        $user = $request->attributes->get('auth_user');
        $cart = $this->buildCart($data, (int) $user['id']);

        return response()->json([
            'message' => 'Cart fetched successfully.',
            'cart' => $cart,
        ]);
    }

    public function add(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => ['required', 'integer', 'min:1'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $user = $request->attributes->get('auth_user');
        $payload = $validator->validated();
        $productId = (int) $payload['product_id'];
        $quantity = (int) ($payload['quantity'] ?? 1);

        $data = $this->store->update(function (array $data) use ($user, $productId, $quantity) {
            $product = collect($data['products'])->firstWhere('id', $productId);
            if (!$product) {
                $data['_error'] = 'Product not found.';
                return $data;
            }

            $uid = (string) $user['id'];
            if (!isset($data['carts'][$uid]) || !is_array($data['carts'][$uid])) {
                $data['carts'][$uid] = [];
            }

            $lineIndex = collect($data['carts'][$uid])->search(static fn (array $line) => (int) $line['product_id'] === $productId);
            if ($lineIndex === false) {
                $lineId = $this->store->nextId($data['carts'][$uid]);
                $data['carts'][$uid][] = [
                    'id' => $lineId,
                    'product_id' => $productId,
                    'quantity' => min($quantity, (int) $product['stock']),
                ];
            } else {
                $nextQuantity = (int) $data['carts'][$uid][$lineIndex]['quantity'] + $quantity;
                $data['carts'][$uid][$lineIndex]['quantity'] = min($nextQuantity, (int) $product['stock']);
            }

            return $data;
        });

        if (isset($data['_error'])) {
            return response()->json(['message' => $data['_error']], 404);
        }

        $cart = $this->buildCart($data, (int) $user['id']);

        return response()->json([
            'message' => 'Cart updated successfully.',
            'cart' => $cart,
        ]);
    }

    public function remove(Request $request, int $id): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        $data = $this->store->update(function (array $data) use ($user, $id) {
            $uid = (string) $user['id'];
            $lines = collect($data['carts'][$uid] ?? [])->reject(static fn (array $item) => (int) $item['id'] === $id)->values();
            $data['carts'][$uid] = $lines->all();
            return $data;
        });

        $cart = $this->buildCart($data, (int) $user['id']);

        return response()->json([
            'message' => 'Cart item removed.',
            'cart' => $cart,
        ]);
    }

    private function buildCart(array $data, int $userId): array
    {
        $lines = collect($data['carts'][(string) $userId] ?? [])->values();
        $productsById = collect($data['products'])->keyBy('id');

        $items = $lines->map(function (array $line) use ($productsById) {
            $product = $productsById->get((int) $line['product_id']);
            if (!$product) {
                return null;
            }

            $quantity = (int) $line['quantity'];
            $price = (float) $product['price'];

            return [
                'id' => (int) $line['id'],
                'product_id' => (int) $product['id'],
                'name' => (string) $product['name'],
                'price' => $price,
                'quantity' => $quantity,
                'line_total' => $price * $quantity,
                'stock' => (int) $product['stock'],
                'image' => (string) $product['image'],
            ];
        })->filter()->values();

        $subtotal = (float) $items->sum('line_total');

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'item_count' => (int) $items->sum('quantity'),
        ];
    }
}
