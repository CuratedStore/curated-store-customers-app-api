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

## CI/CD Deployment (Production)

### Workflow
- GitHub Actions workflow: `.github/workflows/deploy-main.yml`
- Trigger: push to `main`
- Target server path used by workflow:
	- `/home/u686550969/domains/curatedstore.in/customers-api/public_html`

### Required GitHub Secrets
- `SSH_HOST`
- `SSH_PORT`
- `SSH_USER`
- `SSH_PRIVATE_KEY`
- `PROD_APP_NAME`
- `PROD_APP_KEY`
- `PROD_CUSTOMERS_API_URL`
- `PROD_DB_HOST`
- `PROD_DB_PORT`
- `PROD_DB_DATABASE`
- `PROD_DB_USERNAME`
- `PROD_DB_PASSWORD`
- `PROD_SESSION_DOMAIN`
- `PROD_MAIL_HOST`
- `PROD_MAIL_PORT`
- `PROD_MAIL_USERNAME`
- `PROD_MAIL_PASSWORD`
- `PROD_MAIL_ENCRYPTION`
- `PROD_MAIL_FROM_ADDRESS`
- `PROD_MAIL_FROM_NAME`

### hPanel Subdomain Mapping (Important)
If `customers-api.curatedstore.in` is configured to this hPanel folder:
- `/home/u686550969/domains/curatedstore.in/public_html/customers-api`

then ensure it contains the deployed API files (not Hostinger placeholder files).

Recommended mapping is to point subdomain document root directly to:
- `/home/u686550969/domains/curatedstore.in/customers-api/public_html`

### Fallback Sync Command
If hPanel cannot map the subdomain root directly, run this to mirror files:

```bash
rsync -a --delete /home/u686550969/domains/curatedstore.in/customers-api/public_html/ /home/u686550969/domains/curatedstore.in/public_html/customers-api/
```

### Post-Deploy Verification
- Landing page should open Swagger UI at root:
	- `https://customers-api.curatedstore.in/`
- Swagger docs endpoint:
	- `https://customers-api.curatedstore.in/api/documentation`

Quick header check:

```bash
curl -Ik https://customers-api.curatedstore.in/
curl -Ik https://customers-api.curatedstore.in/api/documentation
```
