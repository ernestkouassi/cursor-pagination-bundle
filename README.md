# e2k/cursor-pagination-bundle

Symfony bundle for cursor-based (keyset) pagination with a rich filter expression DSL.

Cursor pagination is O(1) regardless of page depth — unlike OFFSET pagination which degrades linearly.

---

## Installation

```bash
composer require e2k/cursor-pagination-bundle
```

Register the bundle in `config/bundles.php`:

```php
return [
    // ...
    E2k\CursorPaginationBundle\CursorPaginationBundle::class => ['all' => true],
];
```

---

## Quick Start

### 1. Configure the query in your repository

```php
use E2k\CursorPaginationBundle\CursorFieldDefinition;
use E2k\CursorPaginationBundle\FieldDefinition;
use E2k\CursorPaginationBundle\Pagination\CursorQueryFactory;
use E2k\CursorPaginationBundle\Pagination\CursorResult;

class InvoiceRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly CursorQueryFactory $cursorQueryFactory,
    ) {
        parent::__construct($registry, Invoice::class);
    }

    public function findPageByCursor(array $queryParams, int $limit): CursorResult
    {
        return $this->cursorQueryFactory
            ->create(Invoice::class, 'i')
            ->addCursorField(new CursorFieldDefinition('createdAt', 'i.createdAt', 'datetime'))
            ->addCursorField(new CursorFieldDefinition('id', 'i.id', 'string'))
            ->addFilterableField(new FieldDefinition('status',         'i.status.value'))
            ->addFilterableField(new FieldDefinition('organizationId', 'i.organization'))
            ->addFilterableField(new FieldDefinition('amount',         'i.amount', 'float'))
            ->addFilterableField(new FieldDefinition('reference',      'i.reference'))
            ->execute($queryParams, $limit);
    }
}
```

### 2. Use the result in your controller

```php
public function list(Request $request, InvoiceRepository $repository): JsonResponse
{
    $limit  = max(1, min(100, (int) $request->query->get('itemPerPage', 20)));
    $result = $repository->findPageByCursor($request->query->all(), $limit);

    $items = $this->normalizer->normalize(
        $result->items,
        null,
        ['groups' => ['invoice:read']],
    );

    return $this->json($result->toResponseArray($items));
}
```

### 3. Response format

```json
{
    "itemPerPage": 20,
    "nextCursor": "eyJjcmVhdGVkQXQiOiIyMDI0LTAxLTAxVDAwOjAwOjAwLjAwMFoiLCJpZCI6IjEyMyJ9",
    "hasMore": true,
    "filters": {
        "status": "DRAFT",
        "sort": "createdAt",
        "desc": "createdAt"
    },
    "items": [...]
}
```

> **Navigation arrière** : cette API ne fournit pas de `previousCursor`. Pour naviguer en arrière, le client maintient un stack de curseurs côté frontend :
> ```js
> const stack = [];
> // page suivante : stack.push(currentCursor); navigate(nextCursor)
> // page précédente : navigate(stack.pop() ?? null)
> ```

---

## HTTP API Reference

### Pagination parameters

| Parameter | Description | Example |
|-----------|-------------|---------|
| `itemPerPage` | Items per page (handled by your controller) | `?itemPerPage=20` |
| `cursor`      | Opaque cursor from previous response | `?cursor=eyJ...` |

### Sorting (oka_pagination-compatible)

| Parameters | Result |
|------------|--------|
| `sort=createdAt&desc=createdAt` | `ORDER BY createdAt DESC` |
| `sort=createdAt&asc=createdAt` | `ORDER BY createdAt ASC` |
| `sort=createdAt&sort=amount&desc=createdAt&asc=amount` | `ORDER BY createdAt DESC, amount ASC` |

When no direction is specified for a sort field, `ASC` is used by default.

### Filter expressions

Any field declared with `addFilterableField()` accepts the following expressions as its query param value:

| Expression | SQL generated | Example |
|------------|---------------|---------|
| `value` | `field = 'value'` | `?status=DRAFT` |
| `neq(value)` | `field != 'value'` | `?status=neq(DRAFT)` |
| `like(value)` | `field LIKE '%value%'` | `?name=like(acme)` |
| `like(value%)` | `field LIKE 'value%'` | `?name=like(acme%)` |
| `like(%value)` | `field LIKE '%value'` | `?name=like(%acme)` |
| `like(%value%)` | `field LIKE '%value%'` | `?name=like(%acme%)` |
| `in(a,b,c)` | `field IN ('a','b','c')` | `?status=in(DRAFT,SENT)` |
| `gt(value)` | `field > value` | `?amount=gt(100)` |
| `gte(value)` | `field >= value` | `?amount=gte(100)` |
| `lt(value)` | `field < value` | `?amount=lt(500)` |
| `lte(value)` | `field <= value` | `?amount=lte(500)` |
| `range[x,y]` | `x <= field <= y` | `?amount=range[100,500]` |
| `range]x,y[` | `x < field < y` | `?amount=range]100,500[` |
| `range[x,y[` | `x <= field < y` | `?amount=range[100,500[` |
| `range]x,y]` | `x < field <= y` | `?amount=range]100,500]` |
| `range[x,[` | `field >= x` | `?amount=range[100,[` |
| `range],y]` | `field <= y` | `?amount=range],500]` |

---

## Field Definition

### `FieldDefinition`

Declares a field that can be filtered via query params.

```php
new FieldDefinition(
    paramName: 'amount',       // HTTP query param name
    dqlPath:   'i.amount',     // DQL path used in WHERE clause
    castType:  'float',        // cast type for the value (see below)
)
```

### `CursorFieldDefinition`

Declares a field used to position the cursor. Must match entity getter names (used via PropertyAccess).

```php
new CursorFieldDefinition(
    propertyName: 'createdAt',     // entity property (calls getCreatedAt())
    dqlPath:      'i.createdAt',   // DQL path used in WHERE and ORDER BY
    castType:     'datetime',      // cast type when decoding the cursor
)
```

### Cast types

| Value | PHP type |
|-------|----------|
| `string` (default) | `string` |
| `int` | `int` |
| `float` | `float` |
| `bool` | `bool` |
| `datetime` | `\DateTime` |

---

## How cursor pagination works

The cursor encodes the values of the cursor fields from the last item of the current page (base64-encoded JSON). On the next request, the bundle builds a keyset WHERE clause:

For cursor fields `[createdAt DESC, id DESC]`:

```sql
WHERE (
    i.createdAt < :cursor_cmp_0
    OR (i.createdAt = :cursor_eq_0 AND i.id < :cursor_cmp_1)
)
ORDER BY i.createdAt DESC, i.id DESC
LIMIT 21  -- limit + 1 to detect hasMore
```

This approach guarantees consistent performance regardless of page depth and handles ties on the first cursor field correctly.

---

## Custom filter expressions

Implement `FilterExpressionInterface` and tag your service with `e2k.cursor_pagination.filter_expression`:

```php
use E2k\CursorPaginationBundle\FilterExpression\AbstractFilterExpression;
use E2k\CursorPaginationBundle\FilterExpression\EvaluationResult;

class IsNullFilterExpression extends AbstractFilterExpression
{
    public function evaluate(object $queryBuilder, string $field, mixed $value, string $castType, int &$boundCounter): EvaluationResult
    {
        return new EvaluationResult($queryBuilder->expr()->isNull($field));
    }

    protected static function getExpressionPattern(): string
    {
        return '#^null$#i';
    }
}
```

```yaml
# config/services.yaml
App\FilterExpression\IsNullFilterExpression:
    tags:
        - { name: e2k.cursor_pagination.filter_expression }
```

Usage: `?deletedAt=null`

---

## License

MIT
