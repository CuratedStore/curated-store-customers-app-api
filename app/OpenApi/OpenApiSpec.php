<?php

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Curated Store Customers API",
 *     version="1.0.0",
 *     description="Swagger documentation for the Curated Store Customers API"
 * )
 *
 * @OA\Server(
 *     url="https://customers-api.curatedstore.in",
 *     description="Production"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 *
 * @OA\Tag(name="Health", description="Service health endpoints")
 * @OA\Tag(name="Auth", description="Authentication endpoints")
 * @OA\Tag(name="Catalog", description="Product and category endpoints")
 * @OA\Tag(name="Cart", description="Cart endpoints")
 * @OA\Tag(name="Orders", description="Order endpoints")
 * @OA\Tag(name="Account", description="Customer account endpoints")
 *
 * @OA\Get(
 *     path="/api/health",
 *     tags={"Health"},
 *     summary="Health check",
 *     @OA\Response(response=200, description="Service is healthy")
 * )
 *
 * @OA\Post(
 *     path="/api/api/auth/register",
 *     tags={"Auth"},
 *     summary="Register customer",
 *     @OA\Response(response=200, description="Registered")
 * )
 *
 * @OA\Post(
 *     path="/api/api/auth/login",
 *     tags={"Auth"},
 *     summary="Login customer",
 *     @OA\Response(response=200, description="Logged in")
 * )
 *
 * @OA\Get(
 *     path="/api/api/products",
 *     tags={"Catalog"},
 *     summary="List products",
 *     security={{"bearerAuth": {}}},
 *     @OA\Response(response=200, description="Products list")
 * )
 *
 * @OA\Get(
 *     path="/api/api/categories",
 *     tags={"Catalog"},
 *     summary="List categories",
 *     security={{"bearerAuth": {}}},
 *     @OA\Response(response=200, description="Categories list")
 * )
 *
 * @OA\Get(
 *     path="/api/api/cart",
 *     tags={"Cart"},
 *     summary="Get cart",
 *     security={{"bearerAuth": {}}},
 *     @OA\Response(response=200, description="Cart payload")
 * )
 *
 * @OA\Post(
 *     path="/api/api/cart/add",
 *     tags={"Cart"},
 *     summary="Add cart item",
 *     security={{"bearerAuth": {}}},
 *     @OA\Response(response=200, description="Item added")
 * )
 *
 * @OA\Get(
 *     path="/api/api/orders",
 *     tags={"Orders"},
 *     summary="List customer orders",
 *     security={{"bearerAuth": {}}},
 *     @OA\Response(response=200, description="Orders list")
 * )
 *
 * @OA\Post(
 *     path="/api/api/orders",
 *     tags={"Orders"},
 *     summary="Create order",
 *     security={{"bearerAuth": {}}},
 *     @OA\Response(response=200, description="Order created")
 * )
 *
 * @OA\Get(
 *     path="/api/api/account/profile",
 *     tags={"Account"},
 *     summary="Get customer profile",
 *     security={{"bearerAuth": {}}},
 *     @OA\Response(response=200, description="Profile payload")
 * )
 */
class OpenApiSpec
{
}
