# Order & Payment Management API

A Laravel REST API for managing orders and payments with JWT authentication, a filter-based query pipeline, standardized JSON responses, and an extensible payment gateway system built on the **Strategy pattern**.

## Features

- **Order Management** — Create, read, update, and delete orders with automatic total calculation
- **Payment Processing** — Simulated payment processing via pluggable gateways (Credit Card, PayPal)
- **Payment Gateway Configuration** — Configure credentials via `.env` or the `payment_gateways` database table
- **JWT Authentication** — Secure registration, login, and protected endpoints
- **Filter Pipeline** — Extensible query filters using Laravel's Pipeline (`Filterable` trait)
- **Standardized API Responses** — Consistent envelope via `ApiResponse` trait
- **Validation** — Form request validation with meaningful error messages
- **Pagination** — List endpoints support `per_page` and filter query parameters
- **Business Rules**
  - Payments only allowed for **confirmed** orders
  - Orders with payments **cannot be deleted**

## Requirements

- PHP 8.2+
- Composer
- SQLite (default) or MySQL/PostgreSQL

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate
php artisan db:seed --class=PaymentGatewaySeeder
php artisan serve
```

The API is available at `http://localhost:8000/api`.

### Environment Variables

```env
JWT_SECRET=your-jwt-secret

# Payment gateway credentials (simulated — can also be stored in DB)
PAYMENT_CREDIT_CARD_API_KEY=your_api_key
PAYMENT_CREDIT_CARD_SECRET=your_secret
PAYPAL_CLIENT_ID=your_client_id
PAYPAL_CLIENT_SECRET=your_client_secret
```

## API Response Format

All endpoints return a consistent JSON envelope:

```json
{
  "custom_code": 2000,
  "status": true,
  "message": "Data retrieved successfully.",
  "body": {}
}
```

| Field | Description |
|-------|-------------|
| `custom_code` | Application-specific status code (default `2000`) |
| `status` | `true` only when HTTP status is exactly `200` |
| `message` | Human-readable message |
| `body` | Response payload (object) |

Validation errors (422) and auth errors (401) follow Laravel's standard error format.

## API Endpoints

### Authentication

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | `/api/auth/register` | Register a new user | No |
| POST | `/api/auth/login` | Login and get JWT token | No |
| GET | `/api/auth/me` | Get authenticated user | Yes |
| POST | `/api/auth/logout` | Invalidate token | Yes |

### Orders

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/orders` | List orders (paginated, filterable) | Yes |
| POST | `/api/orders` | Create order | Yes |
| GET | `/api/orders/{id}` | Get order details | Yes |
| PUT | `/api/orders/{id}` | Update order | Yes |
| DELETE | `/api/orders/{id}` | Delete order (no payments) | Yes |

**Query parameters:** `?status=pending|confirmed|cancelled`, `?search=`, `?per_page=15`

### Payments

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/payments` | List payments (paginated, filterable) | Yes |
| GET | `/api/payments/{id}` | Get payment details | Yes |
| POST | `/api/orders/{id}/payments` | Process payment for an order | Yes |

**Query parameters:** `?order_id=`, `?status=`, `?payment_method=`, `?search=`, `?per_page=15`

### Payment Gateways (Configuration)

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/payment-gateways` | List all gateway configs | Yes |
| GET | `/api/payment-gateways/{id}` | Get gateway config (secrets masked) | Yes |
| POST | `/api/payment-gateways` | Create gateway config | Yes |
| PUT | `/api/payment-gateways/{id}` | Update config / toggle active | Yes |
| DELETE | `/api/payment-gateways/{id}` | Remove DB config | Yes |

## Example Workflow

```bash
# 1. Register
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Jane","email":"jane@example.com","password":"password123","password_confirmation":"password123"}'

# 2. Create order
curl -X POST http://localhost:8000/api/orders \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"customer_name":"John","customer_email":"john@example.com","items":[{"product_name":"Widget","quantity":2,"price":25.50}]}'

# 3. Confirm order
curl -X PUT http://localhost:8000/api/orders/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"status":"confirmed"}'

# 4. Process payment
curl -X POST http://localhost:8000/api/orders/1/payments \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"payment_method":"credit_card","card_number":"4111111111111111"}'
```

## Payment Gateway Configuration

Gateways support **dual configuration**: `.env` / `config/payment.php` and the `payment_gateways` database table.

### Priority

1. **`.env` / config file** — default credentials
2. **Database** — overrides `.env` for the same gateway name
3. **Inactive DB record** — disables a gateway even if present in config

```
.env / config/payment.php
        ↓
PaymentGatewayConfigRepository  ← merges with DB
        ↓
PaymentGatewayFactory           ← injects config into gateway
        ↓
PaymentGatewayManager           ← resolves at runtime
```

### Seed from .env

```bash
php artisan db:seed --class=PaymentGatewaySeeder
```

### Configure via API

```bash
# Update credit card credentials in database
curl -X PUT http://localhost:8000/api/payment-gateways/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"config":{"api_key":"new_db_api_key","secret":"new_db_secret"}}'

# Deactivate a gateway
curl -X PUT http://localhost:8000/api/payment-gateways/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"is_active":false}'
```

Sensitive fields (`api_key`, `secret`, `client_id`, `client_secret`) are masked in API responses.

### Simulated Payment Behavior

- **Credit Card** — succeeds except card numbers ending in `0000`
- **PayPal** — succeeds except emails containing `fail`

## Adding a New Payment Gateway

### Step 1: Create the Gateway Class

Extend `AbstractGateway` and implement `PaymentGatewayInterface`:

```php
<?php

namespace App\Payments\Gateways;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Payments\AbstractGateway;
use App\Payments\DTOs\PaymentResult;
use Illuminate\Support\Str;

class StripeGateway extends AbstractGateway
{
    public function getName(): string
    {
        return 'stripe';
    }

    public function supports(string $paymentMethod): bool
    {
        return $paymentMethod === PaymentMethod::CreditCard->value;
    }

    public function process(Order $order, array $payload): PaymentResult
    {
        $secretKey = $this->config('secret_key');

        // Integrate with Stripe SDK here...

        return new PaymentResult(
            status: PaymentStatus::Successful,
            transactionReference: 'stripe_'.Str::uuid(),
            gatewayResponse: ['gateway' => $this->getName()],
        );
    }
}
```

### Step 2: Register in Config and/or Database

**Option A — `.env` / config file** (`config/payment.php`):

```php
'stripe' => [
    'driver' => \App\Payments\Gateways\StripeGateway::class,
    'secret_key' => env('STRIPE_SECRET_KEY'),
    'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
],
```

**Option B — Database via API:**

```json
POST /api/payment-gateways
{
  "name": "stripe",
  "driver_class": "App\\Payments\\Gateways\\StripeGateway",
  "is_active": true,
  "config": {
    "secret_key": "sk_live_...",
    "publishable_key": "pk_live_..."
  }
}
```

### Step 3: (Optional) Add Payment Method Enum

If the gateway supports a new payment method, add it to `app/Enums/PaymentMethod.php`.

The `PaymentServiceProvider` auto-registers all active gateways. The `PaymentGatewayManager` resolves the correct gateway based on `payment_method` via the `supports()` method.

## Filter Architecture

List endpoints use the **Pipeline pattern** via the `Filterable` trait on `BaseModel`:

```php
$orders = Order::with('items')
    ->filter([
        GeneralSearch::class,
        OrderStatusFilter::class,
    ])
    ->latest()
    ->paginate(request('per_page', $this->perPage));
```

### Adding a New Filter

1. Create a class extending `App\Support\Filters\Filter`
2. Implement `apply(Builder $query)`
3. Register it in the controller's `->filter([...])` array

```php
// app/Filters/Orders/OrderDateFilter.php
class OrderDateFilter extends Filter
{
    protected function apply(Builder $query): void
    {
        if ($this->filled('from_date')) {
            $query->whereDate('created_at', '>=', $this->input('from_date'));
        }
    }
}
```

Models define searchable columns for `GeneralSearch`:

```php
public function searchableColumns(): array
{
    return ['customer_name', 'customer_email'];
}
```

## Architecture

```
app/
├── Enums/                         # OrderStatus, PaymentStatus, PaymentMethod
├── Filters/
│   ├── Orders/                    # OrderStatusFilter
│   └── Payments/                  # OrderIdFilter, PaymentStatusFilter, ...
├── Http/
│   ├── Controllers/
│   │   ├── ApiController.php      # Base controller (ApiResponse trait)
│   │   └── Api/                   # Auth, Order, Payment, PaymentGateway
│   ├── Requests/                  # Form validation
│   └── Resources/                 # API transformers + BaseResource::paginate()
├── Models/
│   ├── BaseModel.php              # Filterable trait
│   ├── Order.php, Payment.php, PaymentGatewayConfig.php
├── Payments/
│   ├── AbstractGateway.php
│   ├── Contracts/                 # PaymentGatewayInterface
│   ├── DTOs/                      # PaymentResult
│   ├── Gateways/                  # CreditCardGateway, PayPalGateway
│   ├── PaymentGatewayConfigRepository.php
│   ├── PaymentGatewayFactory.php
│   └── PaymentGatewayManager.php
├── Support/
│   ├── Api/ApiResponse.php        # Standard response trait
│   ├── Contracts/Filters/         # FilterContract
│   ├── Filters/                   # Filter base, GeneralSearch
│   └── Traits/Filterable.php
├── Providers/PaymentServiceProvider.php
└── Services/                      # OrderService, PaymentService, PaymentGatewayConfigService
```

## Postman Documentation

Import the collection from:

```
postman/Order & Payment Management API.postman_collection.json
```

The collection includes:
- Pre-configured variables (`base_url`, `access_token`, `order_id`, `payment_gateway_id`)
- Auto-save of JWT token and IDs after login/register/create
- Organized folders: Authentication, Orders, Payments, Payment Gateways
- Request descriptions with success and error response examples

## Testing

```bash
php artisan test
```

Test coverage includes:
- **Unit tests** — Payment gateway logic, config repository merge, filter pipeline
- **Feature tests** — Auth, order CRUD, payment processing, gateway CRUD, business rules

## License

MIT
