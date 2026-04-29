<?php

declare(strict_types=1);

namespace E2k\CursorPaginationBundle\FilterExpression\ORM;

use E2k\CursorPaginationBundle\FilterExpression\AbstractFilterExpression;
use E2k\CursorPaginationBundle\FilterExpression\EvaluationResult;
use E2k\CursorPaginationBundle\FilterExpression\FilterExpressionHandler;

/**
 * Handles: lt(value) → field < value
 *
 * @author Ernest kOUASSI <ernestkouassi02@gmail.com>
 */
class LtFilterExpression extends AbstractFilterExpression
{
    public function evaluate(object $queryBuilder, string $field, mixed $value, string $castType, int &$boundCounter): EvaluationResult
    {
        preg_match(self::getExpressionPattern(), $value, $matches);
        $paramName = 'filter_lt_'.$boundCounter++;

        return new EvaluationResult(
            $queryBuilder->expr()->lt($field, ':'.$paramName),
            [$paramName => FilterExpressionHandler::castValue($matches[1], $castType)],
        );
    }

    protected static function getExpressionPattern(): string
    {
        return '#^lt\((.+)\)$#i';
    }
}
