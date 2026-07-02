# Order and Payment Management API

A Laravel REST API for managing orders and payments with JWT authentication and extensible payment gateway system using the Strategy pattern.

[![PHP Version](https://img.shields.io/badge/PHP-%5E8.3-blue)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-%5E13.8-red)](https://laravel.com)

## Features

- **JWT Authentication** - Secure user registration, login, and logout
- **Order Management** - Create, read, update, delete orders with items
- **Payment Processing** - Process payments through multiple gateways
- **Strategy Pattern** - Easily extensible payment gateway system
- **Service Layer Architecture** - Clean separation of concerns
- **Comprehensive Testing** - 80%+ test coverage with PHPUnit
- **PSR-12 Compliant** - Follows PHP coding standards

## Requirements

- PHP ^8.3
- Composer
- MySQL 5.7+ or MariaDB 10.3+
- Laravel ^13.8

## Installation

1. **Clone the repository**

```bash
git clone <repository-url>
cd Order_Payment_API_Task_Tocaan
```

2. **Install dependencies**

```bash
composer install
```

3. **Configure environment**

```bash
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

4. **Configure database**

Edit `.env` and set your database credentials:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=order_payment_api
DB_USERNAME=root
DB_PASSWORD=
```

5. **Run migrations**

```bash
php artisan migrate
```

6. **Start development server**

```bash
php artisan serve
```

API will be available at: `http://localhost:8000`

## API Documentation

### Base URL

```
http://localhost:8000/api
```

### Authentication

#### Register
```http
POST /api/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

#### Login
```http
POST /api/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}
```

#### Logout (Authenticated)
```http
POST /api/logout
Authorization: Bearer {token}
```

### Orders (All Authenticated)

#### Create Order
```http
POST /api/orders
Authorization: Bearer {token}

{
  "items": [
    {
      "product_name": "Laptop",
      "quantity": 1,
      "unit_price": 999.99
    }
  ]
}
```

#### List Orders
```http
GET /api/orders?status=pending
Authorization: Bearer {token}
```

#### Get Order
```http
GET /api/orders/{id}
Authorization: Bearer {token}
```

#### Update Order (Pending Only)
```http
PUT /api/orders/{id}
Authorization: Bearer {token}

{
  "items": [...]
}
```

#### Confirm Order
```http
PUT /api/orders/{id}/confirm
Authorization: Bearer {token}
```

#### Delete Order (Without Payments Only)
```http
DELETE /api/orders/{id}
Authorization: Bearer {token}
```

### Payments (All Authenticated)

#### Process Payment
```http
POST /api/payments
Authorization: Bearer {token}

{
  "order_id": 1,
  "payment_method": "credit_card",
  "amount": 999.99
}
```

Payment methods: `credit_card`, `paypal`, `stripe`

#### List Payments
```http
GET /api/payments?order_id=1
Authorization: Bearer {token}
```

#### Get Payment
```http
GET /api/payments/{id}
Authorization: Bearer {token}
```

## Order Status Flow

```
pending → confirmed (via /orders/{id}/confirm)
pending → cancelled (manual)
confirmed → cancelled (manual)
```

**Important:** Payments can only be processed for orders in "confirmed" status.

## Payment Gateway System

The API uses the Strategy pattern for payment gateways, making it easy to add new payment methods.

### Available Gateways

- **CreditCardGateway** - Simulated credit card processing
- **PayPalGateway** - Simulated PayPal processing
- **StripeGateway** - Simulated Stripe processing

### Gateway Configuration

Configure success rates in `.env`:

```env
CREDIT_CARD_SUCCESS_RATE=0.8
PAYPAL_SUCCESS_RATE=0.9
STRIPE_SUCCESS_RATE=0.85
```

### Adding a New Payment Gateway

To add a new gateway (e.g., "bank_transfer"):

1. **Create Gateway Class**

```php
// app/Services/PaymentGateways/BankTransferGateway.php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\PaymentResult;
use App\Models\Payment;

class BankTransferGateway implements PaymentGatewayInterface
{
    public function process(Payment $payment): PaymentResult
    {
        // Implement payment processing logic
    }

    public function getName(): string
    {
        return 'Bank Transfer';
    }

    public function validate(array $data): bool
    {
        return isset($data['amount']) && $data['amount'] > 0;
    }
}
```

2. **Register in PaymentGatewayManager**

```php
// app/Services/PaymentGatewayManager.php

public function gateway(string $method): PaymentGatewayInterface
{
    return match ($method) {
        'credit_card' => app(CreditCardGateway::class),
        'paypal' => app(PayPalGateway::class),
        'stripe' => app(StripeGateway::class),
        'bank_transfer' => app(BankTransferGateway::class), // Add this
        default => throw new UnsupportedPaymentMethodException(...)
    };
}
```

3. **Update Database Enum (Optional)**

Create migration to add 'bank_transfer' to payment_method enum.

4. **Update Form Request Validation**

```php
// app/Http/Requests/Payments/StorePaymentRequest.php

'payment_method' => ['required', 'in:credit_card,paypal,stripe,bank_transfer'],
```

That's it! No changes needed to controllers, models, or other services.

## Testing

### Run All Tests

```bash
php artisan test
```

### Run Specific Test Suite

```bash
php artisan test --filter=PaymentGateways
php artisan test --filter=AuthenticationTest
php artisan test --filter=OrderManagementTest
php artisan test --filter=PaymentProcessingTest
```

### Generate Coverage Report

```bash
php artisan test --coverage
```

## Code Quality

### Run Laravel Pint (PSR-12 Formatting)

```bash
./vendor/bin/pint
```

## Postman Collection

Import the Postman collection from `docs/postman/Order_Payment_API.postman_collection.json` to test all API endpoints.

## Architecture

### Layers

```
API Layer (Controllers)
    ↓
Service Layer (Business Logic)
    ↓
Payment Strategy Layer (Gateway Implementations)
    ↓
Data Layer (Eloquent Models)
```

### Key Design Patterns

- **Service Layer Pattern** - Business logic separated from controllers
- **Strategy Pattern** - Extensible payment gateway system
- **Repository Pattern** - Eloquent ORM with clean model relationships
- **Form Request Validation** - Validation logic separated into request classes

## Business Rules

1. Orders are created with "pending" status
2. Orders must be manually confirmed before payment processing
3. Only "confirmed" orders can accept payments
4. Orders with payments cannot be deleted
5. Only "pending" orders can be updated
6. Payment amount must match order total
7. Users can only access their own orders and payments

## Security

- Passwords hashed with bcrypt
- JWT tokens for stateless authentication
- Mass assignment protection on all models
- Authorization checks on all resource access
- Input validation on all endpoints
- SQL injection prevention via Eloquent ORM

## License

MIT License

---

**Built with Laravel 13**
