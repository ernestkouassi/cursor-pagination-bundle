<?php

declare(strict_types=1);

namespace E2k\CursorPaginationBundle\FilterExpression;

/**
 * @author Ernest kOUASSI <ernestkouassi02@gmail.com>
 */
abstract class AbstractFilterExpression implements FilterExpressionInterface
{
    public function supports(object $queryBuilder, mixed $value): bool
    {
        return is_string($value) && (bool) preg_match(static::getExpressionPattern(), $value);
    }

    abstract protected static function getExpressionPattern(): string;
}
