# Domain-Driven Design for Laravel APP - Implementation Guide

> **Purpose**: Enterprise-grade, evolvable architecture playbook for Laravel APP services using DDD principles.

---

## 🎯 Quick Decision Matrix

| Scenario | Pattern | Use When |
|----------|---------|----------|
| Simple CRUD | Transaction Script | Basic data operations, minimal business logic |
| Complex Business Rules | Aggregates + Events | Rich domain logic, invariants to protect |
| Audit/Time Travel | Event Sourcing | Need complete history, regulatory compliance |
| Read/Write Asymmetry | CQRS | Different optimization needs for reads vs writes |
| Cross-Aggregate Workflows | Sagas/Process Managers | Long-running, multi-step business processes |

---

## 📐 Strategic Design Patterns

### 1. Domain Classification
```
Core Domain     → Build in-house (competitive advantage)
Supporting      → Partial build/outsource (necessary but not unique)  
Generic         → Buy/integrate (commodity services)
```

### 2. Bounded Context Rules
- Each model lives within clear boundaries
- The Same term can mean different things in different contexts
- Always create context maps to visualize relationships

### 3. Context Relationships
- **Shared Kernel**: Common code between contexts
- **Customer-Supplier**: Downstream depends on upstream
- **Anti-corruption Layer**: Protect your model from external chaos
- **Open Host Service**: Well-defined APP for integration

---

## 🏗️ Laravel Project Structure

```
├── composer.json
├── artisan
├── .env.example
├── phpunit.xml / pest.php
├── /database/
│   ├── /migrations/
│   └── /seeders/
├── /config/                    # Laravel config files
├── /routes/
│   ├── APP.php
│   └── console.php
├── /public/                    # Entry point
├── /storage/
├── /bootstrap/
├── /tests/                     # Global test helpers
├── /resources/
│   └── /views/
└── /src/                       # 🎯 DDD Structure starts here
    ├── /Shared/                # 🚨 SHARED UTILITIES ONLY
    │   ├── /Infrastructure/
    │   │   ├── /Logging/
    │   │   ├── /Tracing/
    │   │   ├── /EventBus/
    │   │   └── /Http/
    │   └── /Domain/
    │       ├── /ValueObjects/  # Money, Email, etc.
    │       └── /Events/        # Base event classes
    └── /Contexts/              # 🎯 Each bounded context
        └── /Orders/
            ├── /Domain/        # 💎 Pure business logic
            │   ├── /Models/    # Aggregates & Entities
            │   ├── /ValueObjects/
            │   ├── /Events/
            │   ├── /Exceptions/
            │   ├── /Policies/
            │   └── /Repositories/  # Interfaces only
            ├── /Application/   # 🎮 Use cases & orchestration
            │   ├── /Commands/
            │   ├── /Queries/
            │   ├── /Handlers/
            │   ├── /DTOs/
            │   ├── /Services/  # Sagas & orchestration
            │   └── /Ports/     # Outbound interfaces
            ├── /Infrastructure/ # ⚙️ Framework & external
            │   ├── /Persistence/
            │   │   ├── /Eloquent/
            │   │   ├── /Doctrine/
            │   │   └── /Repositories/  # Implementations
            │   ├── /Http/
            │   │   ├── /Controllers/
            │   │   ├── /Requests/
            │   │   ├── /Resources/
            │   │   └── /Middleware/
            │   ├── /Console/
            │   │   └── /Commands/
            │   ├── /Events/
            │   │   ├── /Listeners/
            │   │   └── /Subscribers/
            │   ├── /Jobs/
            │   ├── /Providers/
            │   │   └── OrdersServiceProvider.php
            │   └── /External/  # 3rd party adapters
            └── /Tests/         # 🧪 All test types
                ├── /Unit/
                ├── /Integration/
                └── /Feature/
```

---

## 💎 Domain Layer (Pure Business Logic)

### Structure
```
/Domain/
├── /Models/
│   ├── Order.php              # Aggregate root
│   ├── OrderItem.php          # Entity
│   └── Customer.php           # Another aggregate
├── /ValueObjects/
│   ├── Money.php
│   ├── OrderId.php
│   ├── OrderStatus.php
│   └── Address.php
├── /Events/
│   ├── OrderPlaced.php
│   └── OrderPaid.php
├── /Exceptions/
│   ├── InvalidOrderStateException.php
│   └── OrderNotFoundException.php
├── /Policies/
│   └── OrderPolicy.php        # Business rules
└── /Repositories/
    └── OrderRepositoryInterface.php
```

### Example Aggregate
```php
<?php

namespace Src\Contexts\Orders\Domain\Models;

use Src\Contexts\Orders\Domain\ValueObjects\OrderId;
use Src\Contexts\Orders\Domain\ValueObjects\CustomerId;
use Src\Contexts\Orders\Domain\ValueObjects\OrderStatus;
use Src\Contexts\Orders\Domain\ValueObjects\Money;
use Src\Contexts\Orders\Domain\Events\OrderPlaced;
use Src\Contexts\Orders\Domain\Events\OrderPaid;
use Src\Contexts\Orders\Domain\Exceptions\InvalidOrderStateException;

final class Order
{
    private array $domainEvents = [];

    private function __construct(
        private readonly OrderId $id,
        private readonly CustomerId $customerId,
        private OrderStatus $status,
        private array $items,
        private readonly Money $total,
    ) {}

    public static function place(
        OrderId $id,
        CustomerId $customerId,
        array $items
    ): self {
        $order = new self(
            id: $id,
            customerId: $customerId,
            status: OrderStatus::pending(),
            items: $items,
            total: self::calculateTotal($items),
        );

        $order->raise(new OrderPlaced($id, $customerId));

        return $order;
    }

    public function pay(): void
    {
        if (!$this->status->isPending()) {
            throw new InvalidOrderStateException(
                'Order can only be paid when in pending status'
            );
        }

        $this->status = OrderStatus::paid();
        $this->raise(new OrderPaid($this->id));
    }

    public function cancel(): void
    {
        if ($this->status->isPaid() || $this->status->isShipped()) {
            throw new InvalidOrderStateException(
                'Cannot cancel paid or shipped orders'
            );
        }

        $this->status = OrderStatus::cancelled();
    }

    private function raise(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }

    private static function calculateTotal(array $items): Money
    {
        return array_reduce(
            $items,
            fn(Money $carry, OrderItem $item) => $carry->add($item->subtotal()),
            Money::zero()
        );
    }

    // Getters
    public function id(): OrderId { return $this->id; }
    public function customerId(): CustomerId { return $this->customerId; }
    public function status(): OrderStatus { return $this->status; }
    public function items(): array { return $this->items; }
    public function total(): Money { return $this->total; }
}
```

### Value Objects
```php
<?php

namespace Src\Contexts\Orders\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class Money
{
    private function __construct(
        private int $amount, // Store in cents
        private string $currency
    ) {
        if ($amount < 0) {
            throw new InvalidArgumentException('Money amount cannot be negative');
        }
    }

    public static function fromAmount(float $amount, string $currency = 'USD'): self
    {
        return new self((int) round($amount * 100), strtoupper($currency));
    }

    public static function zero(string $currency = 'USD'): self
    {
        return new self(0, $currency);
    }

    public function add(Money $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(Money $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->amount - $other->amount, $this->currency);
    }

    public function multiply(float $multiplier): self
    {
        return new self((int) round($this->amount * $multiplier), $this->currency);
    }

    public function isGreaterThan(Money $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount > $other->amount;
    }

    private function assertSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Cannot operate on different currencies');
        }
    }

    public function amount(): int { return $this->amount; }
    public function amountInDollars(): float { return $this->amount / 100; }
    public function currency(): string { return $this->currency; }

    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount 
            && $this->currency === $other->currency;
    }
}
```

```php
<?php

namespace Src\Contexts\Orders\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class OrderStatus
{
    private const PENDING = 'pending';
    private const PAID = 'paid';
    private const SHIPPED = 'shipped';
    private const CANCELLED = 'cancelled';

    private function __construct(
        private string $value
    ) {
        if (!in_array($value, [self::PENDING, self::PAID, self::SHIPPED, self::CANCELLED])) {
            throw new InvalidArgumentException("Invalid order status: {$value}");
        }
    }

    public static function pending(): self { return new self(self::PENDING); }
    public static function paid(): self { return new self(self::PAID); }
    public static function shipped(): self { return new self(self::SHIPPED); }
    public static function cancelled(): self { return new self(self::CANCELLED); }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function isPending(): bool { return $this->value === self::PENDING; }
    public function isPaid(): bool { return $this->value === self::PAID; }
    public function isShipped(): bool { return $this->value === self::SHIPPED; }
    public function isCancelled(): bool { return $this->value === self::CANCELLED; }

    public function toString(): string { return $this->value; }

    public function equals(OrderStatus $other): bool
    {
        return $this->value === $other->value;
    }
}
```

### Repository Interface (in Domain)
```php
<?php

namespace Src\Contexts\Orders\Domain\Repositories;

use Src\Contexts\Orders\Domain\Models\Order;
use Src\Contexts\Orders\Domain\ValueObjects\OrderId;
use Src\Contexts\Orders\Domain\ValueObjects\CustomerId;

interface OrderRepositoryInterface
{
    public function save(Order $order): void;
    
    public function findById(OrderId $id): ?Order;
    
    public function findByCustomer(CustomerId $customerId): array;
    
    public function nextIdentity(): OrderId;
}
```

---

## 🎮 Application Layer (Use Cases)

### Structure
```
/Application/
├── /Commands/
│   ├── PlaceOrderCommand.php
│   ├── PayOrderCommand.php
│   └── CancelOrderCommand.php
├── /Queries/
│   ├── GetOrderQuery.php
│   └── ListOrdersQuery.php
├── /Handlers/
│   ├── /Commands/
│   │   ├── PlaceOrderHandler.php
│   │   ├── PayOrderHandler.php
│   │   └── CancelOrderHandler.php
│   └── /Queries/
│       ├── GetOrderHandler.php
│       └── ListOrdersHandler.php
├── /DTOs/
│   ├── OrderDTO.php
│   └── ItemDTO.php
├── /Services/
│   └── OrderSaga.php
└── /Ports/
    ├── PaymentServiceInterface.php
    └── InventoryServiceInterface.php
```

### Example Command & Handler
```php
<?php

namespace Src\Contexts\Orders\Application\Commands;

final readonly class PlaceOrderCommand
{
    public function __construct(
        public string $customerId,
        public array $items, // Array of ['productId' => string, 'quantity' => int, 'price' => float]
    ) {}
}
```

```php
<?php

namespace Src\Contexts\Orders\Application\Handlers\Commands;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Src\Contexts\Orders\Application\Commands\PlaceOrderCommand;
use Src\Contexts\Orders\Domain\Models\Order;
use Src\Contexts\Orders\Domain\Models\OrderItem;
use Src\Contexts\Orders\Domain\ValueObjects\OrderId;
use Src\Contexts\Orders\Domain\ValueObjects\CustomerId;
use Src\Contexts\Orders\Domain\ValueObjects\ProductId;
use Src\Contexts\Orders\Domain\ValueObjects\Money;
use Src\Contexts\Orders\Domain\Repositories\OrderRepositoryInterface;
use Src\Shared\Infrastructure\EventBus\EventBusInterface;

final readonly class PlaceOrderHandler
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private EventBusInterface $eventBus,
    ) {}

    public function handle(PlaceOrderCommand $command): OrderId
    {
        return DB::transaction(function () use ($command) {
            // Generate new identity
            $orderId = $this->orderRepository->nextIdentity();
            
            // Map DTOs to domain objects
            $items = array_map(
                fn(array $item) => new OrderItem(
                    productId: ProductId::fromString($item['productId']),
                    quantity: $item['quantity'],
                    price: Money::fromAmount($item['price']),
                ),
                $command->items
            );
            
            // Create aggregate
            $order = Order::place(
                id: $orderId,
                customerId: CustomerId::fromString($command->customerId),
                items: $items,
            );
            
            // Persist aggregate
            $this->orderRepository->save($order);
            
            // Publish domain events
            foreach ($order->pullDomainEvents() as $event) {
                $this->eventBus->publish($event);
            }
            
            Log::info('Order placed successfully', [
                'orderId' => $orderId->toString(),
                'customerId' => $command->customerId,
            ]);
            
            return $orderId;
        });
    }
}
```

### Example Query & Handler
```php
<?php

namespace Src\Contexts\Orders\Application\Queries;

final readonly class GetOrderQuery
{
    public function __construct(
        public string $orderId,
    ) {}
}
```

```php
<?php

namespace Src\Contexts\Orders\Application\Handlers\Queries;

use Src\Contexts\Orders\Application\Queries\GetOrderQuery;
use Src\Contexts\Orders\Application\DTOs\OrderDTO;
use Src\Contexts\Orders\Domain\ValueObjects\OrderId;
use Src\Contexts\Orders\Domain\Repositories\OrderRepositoryInterface;
use Src\Contexts\Orders\Domain\Exceptions\OrderNotFoundException;

final readonly class GetOrderHandler
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
    ) {}

    public function handle(GetOrderQuery $query): OrderDTO
    {
        $order = $this->orderRepository->findById(
            OrderId::fromString($query->orderId)
        );
        
        if ($order === null) {
            throw new OrderNotFoundException("Order {$query->orderId} not found");
        }
        
        return OrderDTO::fromDomain($order);
    }
}
```

### Data Transfer Object
```php
<?php

namespace Src\Contexts\Orders\Application\DTOs;

use Src\Contexts\Orders\Domain\Models\Order;

final readonly class OrderDTO
{
    public function __construct(
        public string $id,
        public string $customerId,
        public string $status,
        public array $items,
        public float $total,
        public string $currency,
    ) {}

    public static function fromDomain(Order $order): self
    {
        return new self(
            id: $order->id()->toString(),
            customerId: $order->customerId()->toString(),
            status: $order->status()->toString(),
            items: array_map(
                fn($item) => [
                    'productId' => $item->productId()->toString(),
                    'quantity' => $item->quantity(),
                    'price' => $item->price()->amountInDollars(),
                ],
                $order->items()
            ),
            total: $order->total()->amountInDollars(),
            currency: $order->total()->currency(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'customerId' => $this->customerId,
            'status' => $this->status,
            'items' => $this->items,
            'total' => $this->total,
            'currency' => $this->currency,
        ];
    }
}
```

---

## 🔌 Infrastructure Layer (Framework & External)

### HTTP Controller
```php
<?php

namespace Src\Contexts\Orders\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Src\Contexts\Orders\Infrastructure\Http\Requests\PlaceOrderRequest;
use Src\Contexts\Orders\Application\Commands\PlaceOrderCommand;
use Src\Contexts\Orders\Application\Queries\GetOrderQuery;
use Src\Contexts\Orders\Application\Handlers\Commands\PlaceOrderHandler;
use Src\Contexts\Orders\Application\Handlers\Queries\GetOrderHandler;
use Src\Contexts\Orders\Domain\Exceptions\OrderNotFoundException;

final class OrdersController
{
    public function __construct(
        private readonly PlaceOrderHandler $placeOrderHandler,
        private readonly GetOrderHandler $getOrderHandler,
    ) {}

    public function store(PlaceOrderRequest $request): JsonResponse
    {
        $command = new PlaceOrderCommand(
            customerId: $request->input('customerId'),
            items: $request->input('items'),
        );
        
        $orderId = $this->placeOrderHandler->handle($command);
        
        return response()->json([
            'orderId' => $orderId->toString(),
        ], Response::HTTP_CREATED);
    }

    public function show(string $orderId): JsonResponse
    {
        try {
            $query = new GetOrderQuery($orderId);
            $orderDTO = $this->getOrderHandler->handle($query);
            
            return response()->json($orderDTO->toArray());
        } catch (OrderNotFoundException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }
}
```

### Form Request
```php
<?php

namespace Src\Contexts\Orders\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Or implement authorization logic
    }

    public function rules(): array
    {
        return [
            'customerId' => ['required', 'string', 'uuid'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.productId' => ['required', 'string', 'uuid'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
```

### Service Provider (Dependency Injection)
```php
<?php

namespace Src\Contexts\Orders\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Contexts\Orders\Domain\Repositories\OrderRepositoryInterface;
use Src\Contexts\Orders\Infrastructure\Persistence\Repositories\EloquentOrderRepository;
use Src\Contexts\Orders\Application\Handlers\Commands\PlaceOrderHandler;
use Src\Contexts\Orders\Application\Handlers\Queries\GetOrderHandler;
use Src\Shared\Infrastructure\EventBus\EventBusInterface;
use Src\Shared\Infrastructure\EventBus\LaravelEventBus;

class OrdersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repository interface to implementation
        $this->app->bind(
            OrderRepositoryInterface::class,
            EloquentOrderRepository::class
        );
        
        // Bind event bus
        $this->app->bind(
            EventBusInterface::class,
            LaravelEventBus::class
        );
        
        // Register command handlers
        $this->app->singleton(PlaceOrderHandler::class);
        $this->app->singleton(GetOrderHandler::class);
    }

    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../Http/routes.php');
        
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
```

---

## 🧪 Testing Strategy

### 1. Domain Tests (Unit with Pest)
```php
<?php

use Src\Contexts\Orders\Domain\Models\Order;
use Src\Contexts\Orders\Domain\Events\OrderPlaced;
use Src\Contexts\Orders\Domain\Exceptions\InvalidOrderStateException;

test('can place an order', function () {
    $order = Order::place($orderId, $customerId, $items);
    
    expect($order->status()->isPending())->toBeTrue();
    expect($order->pullDomainEvents())->toHaveCount(1);
});

test('cannot pay non-pending order', function () {
    $order = Order::place($orderId, $customerId, $items);
    $order->pay();
    
    expect(fn() => $order->pay())
        ->toThrow(InvalidOrderStateException::class);
});
```

### 2. Feature Tests (End-to-End)
```php
<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can place order via APP', function () {
    $response = $this->postJson('/APP/orders', [
        'customerId' => '123e4567-e89b-12d3-a456-426614174000',
        'items' => [
            ['productId' => 'prod-123', 'quantity' => 2, 'price' => 50.00],
        ],
    ]);
    
    $response->assertStatus(201)
        ->assertJsonStructure(['orderId']);
});
```

---

## 🧭 Implementation Rules

### ✅ DO
- Keep domain layer pure (no Laravel dependencies)
- Use domain events for side effects
- Apply CQRS when read/write patterns differ
- Use value objects for domain concepts
- Test domain logic without framework dependencies

### ❌ DON'T
- Put business logic in controllers
- Let Eloquent models be your domain models
- Share database tables between bounded contexts
- Force DDD on simple CRUD operations

---

## 📋 Migration Checklist

### New Laravel Project
- [ ] Install Laravel
- [ ] Define bounded contexts
- [ ] Set up `/src` directory structure
- [ ] Update `composer.json` autoload
- [ ] Implement domain aggregates
- [ ] Create repository implementations
- [ ] Set up service providers
- [ ] Add comprehensive tests
- [ ] Configure observability

### Composer.json Update
```json
{
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Src\\": "src/"
        }
    }
}
```

---

## 🔧 Useful Packages

- **spatie/laravel-data** - DTOs with validation
- **spatie/laravel-event-sourcing** - Event sourcing
- **pestphp/pest** - Modern testing
- **nunomaduro/larastan** - Static analysis

---

**Remember**: Start simple, focus on the domain, and evolve as complexity demands!
