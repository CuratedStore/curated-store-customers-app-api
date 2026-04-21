# Customers App API

RESTful API for the Curated Store Customers Mobile App built with Laravel 12.

## Setup

### Prerequisites
- PHP 8.2+
- Composer
- MySQL/MariaDB

### Installation

1. Install dependencies:
```bash
composer install
```

2. Copy `.env.example` to `.env` and configure:
```bash
cp .env.example .env
php artisan key:generate
```

3. Set up database:
```bash
php artisan migrate
php artisan db:seed
```

4. Start development server:
```bash
php artisan serve
```

## API Endpoints

### Authentication
- `POST /api/auth/register` - Customer registration
- `POST /api/auth/login` - Customer login
- `POST /api/auth/logout` - Logout
- `POST /api/auth/refresh` - Refresh token

### Products
- `GET /api/products` - List all products
- `GET /api/products/{id}` - Get product details
- `GET /api/categories` - List categories

### Cart & Orders
- `POST /api/cart/add` - Add to cart
- `GET /api/cart` - Get cart items
- `DELETE /api/cart/{id}` - Remove from cart
- `POST /api/orders` - Create order
- `GET /api/orders` - Get customer orders
- `GET /api/orders/{id}` - Get order details

### Account
- `GET /api/account/profile` - Get customer profile
- `PUT /api/account/profile` - Update profile
- `GET /api/account/addresses` - Get saved addresses

## Testing

Run tests with:
```bash
php artisan test
```
