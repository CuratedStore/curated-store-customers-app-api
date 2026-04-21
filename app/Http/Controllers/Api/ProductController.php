<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\CustomersDataStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private readonly CustomersDataStore $store)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $data = $this->store->read();
        $products = collect($data['products']);

        $search = strtolower((string) $request->query('search', ''));
        if ($search !== '') {
            $products = $products->filter(function (array $product) use ($search) {
                return str_contains(strtolower((string) $product['name']), $search)
                    || str_contains(strtolower((string) $product['description']), $search);
            });
        }

        $categoryId = (int) $request->query('category_id', 0);
        if ($categoryId > 0) {
            $products = $products->where('category_id', $categoryId);
        }

        $sort = (string) $request->query('sort', 'newest');
        if ($sort === 'price_low_high') {
            $products = $products->sortBy('price')->values();
        } elseif ($sort === 'price_high_low') {
            $products = $products->sortByDesc('price')->values();
        } else {
            $products = $products->sortByDesc('id')->values();
        }

        return response()->json([
            'message' => 'Products fetched successfully.',
            'products' => $products->values(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $data = $this->store->read();
        $product = collect($data['products'])->firstWhere('id', $id);

        if (!$product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        return response()->json([
            'message' => 'Product fetched successfully.',
            'product' => $product,
        ]);
    }
}
