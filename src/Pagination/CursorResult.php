<?php

declare(strict_types=1);

namespace E2k\CursorPaginationBundle\Pagination;

/**
 * Holds the raw output of a cursor query execution.
 * Items are raw entities — normalize them before calling toResponseArray().
 *
 * @author Ernest kOUASSI <ernestkouassi02@gmail.com>
 */
final class CursorResult
{
    public function __construct(
        public readonly array $items,
        public readonly ?string $nextCursor,
        public readonly bool $hasMore,
        public readonly int $limit,
        public readonly array $appliedFilters = [],
    ) {
    }

    /**
     * Builds the API response array from already-normalized items.
     *
     * @param array $normalizedItems result of serializer/normalizer applied to $this->items
     */
    public function toResponseArray(array $normalizedItems): array
    {
        return [
            'itemPerPage' => $this->limit,
            'nextCursor'  => $this->nextCursor,
            'hasMore'     => $this->hasMore,
            'filters'     => $this->appliedFilters,
            'items'       => $normalizedItems,
        ];
    }
}
