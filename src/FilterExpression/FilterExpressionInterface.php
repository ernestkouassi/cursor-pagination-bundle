<?php

declare(strict_types=1);

namespace E2k\CursorPaginationBundle\FilterExpression;

/**
 * @author Ernest kOUASSI <ernestkouassi02@gmail.com>
 */
interface FilterExpressionInterface
{
    public function supports(object $queryBuilder, mixed $value): bool;

    public function evaluate(object $queryBuilder, string $field, mixed $value, string $castType, int &$boundCounter): EvaluationResult;
}
