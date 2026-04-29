<?php

declare(strict_types=1);

namespace E2k\CursorPaginationBundle\FilterExpression\ORM;

use E2k\CursorPaginationBundle\FilterExpression\AbstractFilterExpression;
use E2k\CursorPaginationBundle\FilterExpression\EvaluationResult;
use E2k\CursorPaginationBundle\FilterExpression\FilterExpressionHandler;

/**
 * Handles: in(a,b,c) → field IN ('a','b','c')
 *
 * @author Ernest kOUASSI <ernestkouassi02@gmail.com>
 */
class InFilterExpression extends AbstractFilterExpression
{
    public function evaluate(object $queryBuilder, string $field, mixed $value, string $castType, int &$boundCounter): EvaluationResult
    {
        preg_match(self::getExpressionPattern(), $value, $matches);
        $values = array_map(
            fn (string $v) => FilterExpressionHandler::castValue(trim($v), $castType),
            explode(',', $matches[1]),
        );
        $paramName = 'filter_in_'.$boundCounter++;

        return new EvaluationResult(
            $queryBuilder->expr()->in($field, ':'.$paramName),
            [$paramName => $values],
        );
    }

    protected static function getExpressionPattern(): string
    {
        return '#^in\((.+)\)$#i';
    }
}
