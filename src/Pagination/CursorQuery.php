<?php

declare(strict_types=1);

namespace E2k\CursorPaginationBundle\Pagination;

use Doctrine\ORM\EntityManagerInterface;
use E2k\CursorPaginationBundle\CursorFieldDefinition;
use E2k\CursorPaginationBundle\FieldDefinition;
use E2k\CursorPaginationBundle\FilterExpression\FilterExpressionHandler;
use E2k\CursorPaginationBundle\Sort\SortParser;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @author Ernest kOUASSI <ernestkouassi02@gmail.com>
 */
class CursorQuery
{
    /** @var CursorFieldDefinition[] */
    private array $cursorFields = [];

    /** @var array<string, FieldDefinition> indexed by paramName */
    private array $filterableFields = [];

    private string $alias;

    /** @var callable|null */
    private $queryBuilderDecorator = null;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly string $className,
        private readonly FilterExpressionHandler $filterHandler,
        private readonly CursorEncoder $cursorEncoder,
        private readonly SortParser $sortParser,
        private readonly PropertyAccessorInterface $propertyAccessor,
        string $alias = 'e',
    ) {
        $this->alias = $alias;
    }

    public function addCursorField(CursorFieldDefinition $field): static
    {
        $this->cursorFields[] = $field;

        return $this;
    }

    public function addFilterableField(FieldDefinition $field): static
    {
        $this->filterableFields[$field->paramName] = $field;

        return $this;
    }

    /**
     * Injects a callback to add custom WHERE conditions that can't be expressed
     * through standard filter expressions (e.g. OR conditions, subqueries).
     *
     * The callable receives (QueryBuilder $qb, array $queryParams).
     */
    public function withQueryBuilderDecorator(callable $decorator): static
    {
        $this->queryBuilderDecorator = $decorator;

        return $this;
    }

    public function execute(array $queryParams, int $limit): CursorResult
    {
        $cursorParam = $queryParams['cursor'] ?? null;
        $sortOrders = $this->sortParser->parse($queryParams);

        $qb = $this->em->createQueryBuilder()
            ->select($this->alias)
            ->from($this->className, $this->alias);

        $boundCounter = 1;

        // Apply filters from query params
        foreach ($queryParams as $paramName => $value) {
            if (in_array($paramName, ['cursor', 'itemPerPage', 'sort', 'asc', 'desc'], true)) {
                continue;
            }

            if (!isset($this->filterableFields[$paramName])) {
                continue;
            }

            $fieldDef = $this->filterableFields[$paramName];
            $this->filterHandler->evaluate($qb, $fieldDef->dqlPath, $value, $fieldDef->castType, $boundCounter);
        }

        // Apply custom conditions (subqueries, OR filters, etc.)
        if (null !== $this->queryBuilderDecorator) {
            ($this->queryBuilderDecorator)($qb, $queryParams);
        }

        // Apply cursor WHERE clause
        if (null !== $cursorParam) {
            $cursorData = $this->cursorEncoder->decode($cursorParam);
            $this->applyCursorWhere($qb, $cursorData, $sortOrders);
        }

        // Apply ORDER BY on cursor fields
        foreach ($this->cursorFields as $cursorField) {
            $dir = $sortOrders[$cursorField->propertyName] ?? 'DESC';
            $qb->addOrderBy($cursorField->dqlPath, $dir);
        }

        $results = $qb->setMaxResults($limit + 1)->getQuery()->getResult();

        $hasMore = count($results) > $limit;
        if ($hasMore) {
            array_pop($results);
        }

        $nextCursor = null;

        if ($hasMore && [] !== $results) {
            $nextCursor = $this->cursorEncoder->encode($this->extractCursorData(end($results)));
        }

        $appliedFilters = array_diff_key($queryParams, ['cursor' => true, 'itemPerPage' => true]);

        return new CursorResult($results, $nextCursor, $hasMore, $limit, $appliedFilters);
    }

    /**
     * Builds the keyset WHERE clause for N cursor fields.
     *
     * For cursor fields [f0, f1] both DESC:
     *   WHERE (f0 < :cursor_cmp_0)
     *      OR (f0 = :cursor_eq_0 AND f1 < :cursor_cmp_1)
     */
    private function applyCursorWhere(\Doctrine\ORM\QueryBuilder $qb, array $cursorData, array $sortOrders): void
    {
        $orParts = [];

        foreach ($this->cursorFields as $i => $cursorField) {
            $andParts = [];

            // Equality conditions for all preceding cursor fields
            foreach (array_slice($this->cursorFields, 0, $i) as $j => $prevField) {
                $paramName = 'cursor_eq_'.$j;
                $andParts[] = $qb->expr()->eq($prevField->dqlPath, ':'.$paramName);
                $qb->setParameter($paramName, $this->castCursorValue(
                    $cursorData[$prevField->propertyName] ?? null,
                    $prevField->castType,
                ));
            }

            // Directional condition for the current cursor field
            $dir = $sortOrders[$cursorField->propertyName] ?? 'DESC';
            $paramName = 'cursor_cmp_'.$i;
            $op = 'DESC' === $dir ? 'lt' : 'gt';
            $andParts[] = $qb->expr()->$op($cursorField->dqlPath, ':'.$paramName);
            $qb->setParameter($paramName, $this->castCursorValue(
                $cursorData[$cursorField->propertyName] ?? null,
                $cursorField->castType,
            ));

            $orParts[] = 1 === count($andParts)
                ? $andParts[0]
                : $qb->expr()->andX(...$andParts);
        }

        if ([] === $orParts) {
            return;
        }

        $qb->andWhere(1 === count($orParts)
            ? $orParts[0]
            : $qb->expr()->orX(...$orParts));
    }

    private function extractCursorData(object $entity): array
    {
        $data = [];
        foreach ($this->cursorFields as $cursorField) {
            $value = $this->propertyAccessor->getValue($entity, $cursorField->propertyName);
            if ($value instanceof \DateTimeInterface) {
                $value = $value->format(\DateTimeInterface::RFC3339_EXTENDED);
            }
            $data[$cursorField->propertyName] = $value;
        }

        return $data;
    }

    private function castCursorValue(mixed $value, string $castType): mixed
    {
        if (null === $value) {
            return null;
        }

        return match ($castType) {
            'datetime' => new \DateTime((string) $value),
            'int' => (int) $value,
            'float' => (float) $value,
            default => (string) $value,
        };
    }
}
