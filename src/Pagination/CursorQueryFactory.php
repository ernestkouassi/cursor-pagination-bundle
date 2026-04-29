<?php

declare(strict_types=1);

namespace E2k\CursorPaginationBundle\Pagination;

use Doctrine\ORM\EntityManagerInterface;
use E2k\CursorPaginationBundle\FilterExpression\FilterExpressionHandler;
use E2k\CursorPaginationBundle\Sort\SortParser;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @author Ernest kOUASSI <ernestkouassi02@gmail.com>
 */
class CursorQueryFactory
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FilterExpressionHandler $filterHandler,
        private readonly CursorEncoder $cursorEncoder,
        private readonly SortParser $sortParser,
        private readonly PropertyAccessorInterface $propertyAccessor,
    ) {
    }

    public function create(string $className, string $alias = 'e'): CursorQuery
    {
        return new CursorQuery(
            $this->em,
            $className,
            $this->filterHandler,
            $this->cursorEncoder,
            $this->sortParser,
            $this->propertyAccessor,
            $alias,
        );
    }
}
