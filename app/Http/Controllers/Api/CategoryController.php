<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\CustomersDataStore;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function __construct(private readonly CustomersDataStore $store)
    {
    }

    public function index(): JsonResponse
    {
        $data = $this->store->read();

        return response()->json([
            'message' => 'Categories fetched successfully.',
            'categories' => $data['categories'],
        ]);
    }
}
