# DDD Enhancement Plan - The Last Messenger Backend

## Status: COMPLETED

**Completion Date:** 2025-11-29

---

## Summary of Enhancements

### Phase 1: Shared Infrastructure - COMPLETED

#### 1.1 Base Value Objects (`app/Domain/Shared/ValueObjects/`)
- [x] `EntityId.php` - Base ID value object with validation
- [x] `Slug.php` - URL-friendly string with Unicode support
- [x] `Translation.php` - Multi-language text container (ar, en, tr, fr, ur)
- [x] `DateRange.php` - Hijri/Gregorian date ranges

#### 1.2 Domain Events Infrastructure (`app/Domain/Shared/Events/`)
- [x] `DomainEvent.php` - Base event class with metadata
- [x] `EventDispatcherInterface.php` - Event dispatching contract
- [x] `LaravelEventDispatcher.php` - Laravel integration
- [x] `Traits/HasDomainEvents.php` - Trait for aggregate roots

#### 1.3 Base Exceptions (`app/Domain/Shared/Exceptions/`)
- [x] `DomainException.php` - Base domain exception with context
- [x] `EntityNotFoundException.php` - Resource not found
- [x] `ValidationException.php` - Domain validation failures
- [x] `BusinessRuleViolationException.php` - Invariant violations

---

### Phase 2: Hadith Domain Completion - COMPLETED

#### 2.1 Repositories (`app/Domain/Hadith/Repositories/`)
- [x] `HadithRepositoryInterface.php` + `HadithRepository.php`
- [x] `CollectionRepositoryInterface.php` + `CollectionRepository.php`
- [x] `BookRepositoryInterface.php` + `BookRepository.php`
- [x] `ChapterRepositoryInterface.php` + `ChapterRepository.php`
- [x] `NarratorRepositoryInterface.php` + `NarratorRepository.php`
- [x] `ScholarRepositoryInterface.php` + `ScholarRepository.php`
- [x] `ThemeRepositoryInterface.php` + `ThemeRepository.php`
- [x] `GradingLevelRepositoryInterface.php` + `GradingLevelRepository.php`

#### 2.2 Domain Exceptions (`app/Domain/Hadith/Exceptions/`)
- [x] `HadithNotFoundException.php`
- [x] `CollectionNotFoundException.php`
- [x] `InvalidGradingException.php`

---

### Phase 3: Application Layer (CQRS Lite) - COMPLETED

#### 3.1 Command Bus Infrastructure (`app/Domain/Shared/Application/Commands/`)
- [x] `Command.php` - Base command interface
- [x] `CommandHandler.php` - Handler interface
- [x] `CommandBus.php` - Dispatcher with container integration

#### 3.2 Query Bus Infrastructure (`app/Domain/Shared/Application/Queries/`)
- [x] `Query.php` - Base query interface
- [x] `QueryHandler.php` - Handler interface
- [x] `QueryBus.php` - Dispatcher with container integration

#### 3.3 Shared DTOs (`app/Domain/Shared/Application/DTOs/`)
- [x] `Result.php` - Generic operation result
- [x] `PaginatedResult.php` - Paginated data wrapper

#### 3.4 Hadith Application Layer (`app/Domain/Hadith/Application/`)
- [x] `Queries/GetHadithByIdQuery.php`
- [x] `Queries/SearchHadithQuery.php`
- [x] `DTOs/HadithDTO.php`
- [x] `Handlers/GetHadithByIdHandler.php`
- [x] `Handlers/SearchHadithHandler.php`

---

### Phase 4: API Resources Enhancement - COMPLETED

#### 4.1 Hadith API Resources (`app/Http/Resources/`)
- [x] `HadithResource.php`
- [x] `HadithCollectionResource.php`
- [x] `HadithBookResource.php`
- [x] `HadithChapterResource.php`
- [x] `HadithThemeResource.php`
- [x] `HadithNarratorResource.php`

---

### Service Provider Updates - COMPLETED

- [x] Updated `RepositoryServiceProvider.php` with all Hadith repositories
- [x] Added `EventDispatcherInterface` binding

---

## Final File Structure

```
app/Domain/
├── Shared/
│   ├── ValueObjects/
│   │   ├── EntityId.php
│   │   ├── Slug.php
│   │   ├── Translation.php
│   │   └── DateRange.php
│   ├── Events/
│   │   ├── DomainEvent.php
│   │   ├── EventDispatcherInterface.php
│   │   ├── LaravelEventDispatcher.php
│   │   └── Traits/
│   │       └── HasDomainEvents.php
│   ├── Exceptions/
│   │   ├── DomainException.php
│   │   ├── EntityNotFoundException.php
│   │   ├── ValidationException.php
│   │   └── BusinessRuleViolationException.php
│   ├── Application/
│   │   ├── Commands/
│   │   │   ├── Command.php
│   │   │   ├── CommandHandler.php
│   │   │   └── CommandBus.php
│   │   ├── Queries/
│   │   │   ├── Query.php
│   │   │   ├── QueryHandler.php
│   │   │   └── QueryBus.php
│   │   └── DTOs/
│   │       ├── Result.php
│   │       └── PaginatedResult.php
│   ├── Contracts/
│   │   └── RepositoryInterface.php (existing)
│   └── Repositories/
│       └── BaseRepository.php (existing)
├── Hadith/
│   ├── Models/ (existing)
│   ├── Services/ (existing)
│   ├── Enums/ (existing)
│   ├── ValueObjects/ (existing - SyncStatistics)
│   ├── Repositories/
│   │   ├── HadithRepositoryInterface.php
│   │   ├── HadithRepository.php
│   │   ├── CollectionRepositoryInterface.php
│   │   ├── CollectionRepository.php
│   │   ├── BookRepositoryInterface.php
│   │   ├── BookRepository.php
│   │   ├── ChapterRepositoryInterface.php
│   │   ├── ChapterRepository.php
│   │   ├── NarratorRepositoryInterface.php
│   │   ├── NarratorRepository.php
│   │   ├── ScholarRepositoryInterface.php
│   │   ├── ScholarRepository.php
│   │   ├── ThemeRepositoryInterface.php
│   │   ├── ThemeRepository.php
│   │   ├── GradingLevelRepositoryInterface.php
│   │   └── GradingLevelRepository.php
│   ├── Exceptions/
│   │   ├── HadithNotFoundException.php
│   │   ├── CollectionNotFoundException.php
│   │   └── InvalidGradingException.php
│   └── Application/
│       ├── Commands/
│       ├── Queries/
│       │   ├── GetHadithByIdQuery.php
│       │   └── SearchHadithQuery.php
│       ├── DTOs/
│       │   └── HadithDTO.php
│       └── Handlers/
│           ├── GetHadithByIdHandler.php
│           └── SearchHadithHandler.php
├── Quran/ (existing - repository pattern implemented)
├── Seerah/ (existing - repository pattern implemented)
├── Sahaba/ (existing - repository pattern implemented)
├── Genealogy/ (existing - repository pattern implemented)
└── Blog/ (existing - repository pattern implemented)

app/Http/Resources/
├── HadithResource.php (new)
├── HadithCollectionResource.php (new)
├── HadithBookResource.php (new)
├── HadithChapterResource.php (new)
├── HadithThemeResource.php (new)
├── HadithNarratorResource.php (new)
└── ... (existing resources)
```

---

## Usage Examples

### Using Value Objects

```php
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\Slug;
use App\Domain\Shared\ValueObjects\Translation;

// EntityId
$id = EntityId::fromInt(123);
$id->toInt(); // 123

// Slug
$slug = Slug::fromText('صحيح البخاري');
$slug->value(); // 'صحيح-البخاري'

// Translation
$translation = Translation::fromArray([
    'ar' => 'العنوان',
    'en' => 'Title',
]);
$translation->get('ar'); // 'العنوان'
```

### Using Domain Events

```php
use App\Domain\Shared\Events\Traits\HasDomainEvents;

class HadithAggregate
{
    use HasDomainEvents;

    public function updateGrading(string $newGrade): void
    {
        $this->grade = $newGrade;
        $this->raise(new HadithGradingUpdated($this->id, $newGrade));
    }
}

// In service
$events = $aggregate->pullDomainEvents();
$eventDispatcher->dispatchAll($events);
```

### Using Repositories

```php
use App\Domain\Hadith\Repositories\HadithRepositoryInterface;

class HadithService
{
    public function __construct(
        private HadithRepositoryInterface $repository
    ) {}

    public function getHadith(int $id): ?Hadith
    {
        return $this->repository->getWithFullRelations($id);
    }

    public function searchHadiths(string $query): LengthAwarePaginator
    {
        return $this->repository->search($query);
    }
}
```

### Using Application Layer

```php
use App\Domain\Hadith\Application\Queries\GetHadithByIdQuery;
use App\Domain\Hadith\Application\Handlers\GetHadithByIdHandler;

// In controller
public function show(int $id, GetHadithByIdHandler $handler): JsonResponse
{
    $query = new GetHadithByIdQuery(hadithId: $id, withRelations: true);
    $hadithDTO = $handler->handle($query);

    return response()->json($hadithDTO);
}
```

---

## Architecture Principles Applied

1. **Domain-Driven Design (DDD)** - Clear bounded contexts
2. **Repository Pattern** - Data access abstraction
3. **CQRS Lite** - Separated commands and queries
4. **Value Objects** - Immutable domain primitives
5. **Domain Events** - Event-driven architecture foundation
6. **Dependency Injection** - Container-managed dependencies
7. **Interface Segregation** - Focused contracts

---

## Phase 5: CQRS Enhancement - COMPLETED

**Completion Date:** 2025-11-29

### 5.1 Commands (Write Operations)
- [x] `CreateHadithCommand.php` + `CreateHadithHandler.php`
- [x] `UpdateHadithCommand.php` + `UpdateHadithHandler.php`
- [x] `DeleteHadithCommand.php` + `DeleteHadithHandler.php`
- [x] `SyncHadithsCommand.php` + `SyncHadithsHandler.php`
- [x] `ImportHadithsCommand.php` + `ImportHadithsHandler.php`
- [x] `UpdateGradingCommand.php` + `UpdateGradingHandler.php`
- [x] `CreateCollectionCommand.php` + `CreateCollectionHandler.php`
- [x] `UpdateCollectionCommand.php` + `UpdateCollectionHandler.php`
- [x] `CreateBookCommand.php` + `CreateBookHandler.php`
- [x] `CreateChapterCommand.php` + `CreateChapterHandler.php`
- [x] `CreateNarratorCommand.php` + `CreateNarratorHandler.php`
- [x] `CreateThemeCommand.php` + `CreateThemeHandler.php`

### 5.2 Queries (Read Operations)
- [x] `GetHadithByIdQuery.php` + Handler
- [x] `SearchHadithQuery.php` + Handler
- [x] `GetCollectionByIdQuery.php` + Handler
- [x] `GetCollectionBySlugQuery.php` + Handler
- [x] `ListCollectionsQuery.php` + Handler
- [x] `GetBookByIdQuery.php` + Handler
- [x] `ListBooksByCollectionQuery.php` + Handler
- [x] `GetChapterByIdQuery.php`
- [x] `ListChaptersByBookQuery.php`
- [x] `ListHadithsByBookQuery.php` + Handler
- [x] `ListHadithsByThemeQuery.php` + Handler
- [x] `ListNarratorsQuery.php`
- [x] `ListThemesQuery.php` + Handler
- [x] `GetRandomHadithQuery.php` + Handler

### 5.3 DTOs
- [x] `HadithDTO.php` - Hadith data transfer object
- [x] `CollectionDTO.php` - Collection data transfer object
- [x] `BookDTO.php` - Book data transfer object
- [x] `ChapterDTO.php` - Chapter data transfer object
- [x] `NarratorDTO.php` - Narrator data transfer object
- [x] `ThemeDTO.php` - Theme data transfer object

### 5.4 Domain Events
- [x] `HadithCreated.php` - Event when hadith is created
- [x] `HadithUpdated.php` - Event when hadith is updated
- [x] `HadithDeleted.php` - Event when hadith is deleted
- [x] `HadithGradingUpdated.php` - Event when grading changes
- [x] `HadithsSynced.php` - Event when hadiths are synced from API
- [x] `HadithsImported.php` - Event when hadiths are imported from file
- [x] `CollectionCreated.php` - Event when collection is created
- [x] `BookCreated.php` - Event when book is created

### 5.5 Application Service Facade
- [x] `HadithApplicationService.php` - Orchestrates all CQRS operations

---

## Using the Application Service

```php
use App\Domain\Hadith\Services\HadithApplicationService;

class HadithController
{
    public function __construct(
        private HadithApplicationService $hadithService
    ) {}

    // Create a new hadith
    public function store(Request $request)
    {
        $hadithDTO = $this->hadithService->createHadith(
            bookId: $request->book_id,
            hadithNumber: $request->hadith_number,
            translations: ['ar' => $request->text_ar, 'en' => $request->text_en],
            gradingStatus: $request->grading,
        );

        return response()->json($hadithDTO);
    }

    // Search hadiths
    public function search(Request $request)
    {
        $result = $this->hadithService->searchHadiths(
            searchQuery: $request->q,
            perPage: $request->per_page ?? 15,
            filters: $request->filters ?? [],
        );

        return response()->json($result);
    }

    // Get random hadith
    public function random()
    {
        $hadithDTO = $this->hadithService->getRandomHadith(
            gradingStatus: 'sahih',
        );

        return response()->json($hadithDTO);
    }
}
```

---

## Next Steps (Future Enhancements)

1. Add more domain-specific Value Objects per context
2. Implement Event Sourcing for audit-critical entities
3. Add Specification Pattern for complex queries
4. Add comprehensive unit tests for all CQRS components
5. Implement event listeners for domain events
6. Add caching layer to query handlers
