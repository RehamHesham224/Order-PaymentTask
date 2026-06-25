# Order & Payment Management API

A Laravel REST API for managing orders and payments with JWT authentication and an extensible payment gateway system built on the **Strategy pattern**.

## Features

- **Order Management** — Create, read, update, and delete orders with automatic total calculation
- **Payment Processing** — Simulated payment processing via pluggable gateways (Credit Card, PayPal)
- **JWT Authentication** — Secure registration, login, and protected endpoints
- **Validation** — Form request validation with meaningful error messages
- **Pagination** — List endpoints support `per_page` and filtering
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
php artisan serve
```

The API is available at `http://localhost:8000/api`.

### Environment Variables

```env
JWT_SECRET=your-jwt-secret

# Payment gateway credentials (simulated)
PAYMENT_CREDIT_CARD_API_KEY=your_api_key
PAYMENT_CREDIT_CARD_SECRET=your_secret
PAYPAL_CLIENT_ID=your_client_id
PAYPAL_CLIENT_SECRET=your_client_secret
```

## API Endpoints

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | `/api/auth/register` | Register a new user | No |
| POST | `/api/auth/login` | Login and get JWT token | No |
| GET | `/api/auth/me` | Get authenticated user | Yes |
| POST | `/api/auth/logout` | Invalidate token | Yes |
| GET | `/api/orders` | List orders (`?status=`, `?per_page=`) | Yes |
| POST | `/api/orders` | Create order | Yes |
| GET | `/api/orders/{id}` | Get order details | Yes |
| PUT | `/api/orders/{id}` | Update order | Yes |
| DELETE | `/api/orders/{id}` | Delete order (no payments) | Yes |
| GET | `/api/payments` | List payments (`?order_id=`) | Yes |
| GET | `/api/payments/{id}` | Get payment details | Yes |
| POST | `/api/orders/{id}/payments` | Process payment | Yes |

## Example Workflow

```bash
# 1. Register
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Jane","email":"jane@example.com","password":"password123","password_confirmation":"password123"}'

# 2. Create order (use token from step 1)
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

## Adding a New Payment Gateway

The system uses the **Strategy pattern** so new gateways require minimal changes:

### Step 1: Create the Gateway Class

Implement `PaymentGatewayInterface` in `app/Payments/Gateways/`:

```php
<?php

namespace App\Payments\Gateways;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\DTOs\PaymentResult;
use Illuminate\Support\Str;

class StripeGateway implements PaymentGatewayInterface
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
        $secretKey = config('payment.gateways.stripe.secret_key');

        // Integrate with Stripe SDK here...

        return new PaymentResult(
            status: PaymentStatus::Successful,
            transactionReference: 'stripe_'.Str::uuid(),
            gatewayResponse: ['gateway' => $this->getName()],
        );
    }
}
```

### Step 2: Register in Configuration

Add the gateway to `config/payment.php`:

```php
'gateways' => [
    // ...existing gateways...
    'stripe' => [
        'driver' => \App\Payments\Gateways\StripeGateway::class,
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
    ],
],
```

### Step 3: Add Environment Variables

```env
STRIPE_SECRET_KEY=sk_test_...
STRIPE_PUBLISHABLE_KEY=pk_test_...
```

### Step 4: (Optional) Add Payment Method Enum Value

If the gateway supports a new payment method, add it to `app/Enums/PaymentMethod.php`.

**That's it.** The `PaymentServiceProvider` auto-registers all gateways from config. The `PaymentGatewayManager` resolves the correct gateway based on `payment_method` via the `supports()` method.

## Architecture

```
app/
├── Enums/                  # OrderStatus, PaymentStatus, PaymentMethod
├── Filters/
├── Http/
│   ├── Controllers/Api/    # Auth, Order, Payment controllers
│   ├── Requests/           # Form validation
│   └── Resources/          # API response transformers
├── Models/                 # Order, OrderItem, Payment
├── Payments/
│   ├── Contracts/          # PaymentGatewayInterface
│   ├── DTOs/               # PaymentResult
│   ├── Gateways/           # CreditCardGateway, PayPalGateway
│   └── PaymentGatewayManager.php
├── Support                 # For base components
├── Providers/              # PaymentServiceProvider
└── Services/               # OrderService, PaymentService
```

## Postman Documentation

Import the collection from:

```
postman/Order & Payment Management API.postman_collection.json
```

The collection includes:
- Pre-configured variables (`base_url`, `access_token`, `order_id`)
- Auto-save of JWT token after login/register
- Organized folders: Authentication, Orders, Payments
- Request descriptions with success and error examples

## Testing

```bash
php artisan test
```

Test coverage includes:
- **Unit tests** — Payment gateway logic (success, failure, manager resolution)
- **Feature tests** — Auth, order CRUD, payment processing, business rules

## License

MIT
