<?php

declare(strict_types=1);

namespace E2k\CursorPaginationBundle\FilterExpression\ORM;

use E2k\CursorPaginationBundle\FilterExpression\AbstractFilterExpression;
use E2k\CursorPaginationBundle\FilterExpression\EvaluationResult;
use E2k\CursorPaginationBundle\FilterExpression\FilterExpressionHandler;

/**
 * Handles mathematical interval notation:
 *   range[x, y]  → x <= field <= y
 *   range]x, y[  → x <  field <  y
 *   range[x, y[  → x <= field <  y
 *   range]x,y]  → x <  field <= y
 *   range[x,[   → field >= x
 *   range],y]   → field <= y
 *
 * Left  [ = inclusive (>=), ] = exclusive (>)
 * Right ] = inclusive (<=), [ = exclusive (<)
 *
 * @author Ernest kOUASSI <ernestkouassi02@gmail.com>
 */
class RangeFilterExpression extends AbstractFilterExpression
{
    public function evaluate(object $queryBuilder, string $field, mixed $value, string $castType, int &$boundCounter): EvaluationResult
    {
        preg_match(self::getExpressionPattern(), $value, $m);

        $andX = $queryBuilder->expr()->andX();
        $params = [];

        if ('' !== $m['start']) {
            $paramName = 'filter_range_start_'.$boundCounter++;
            $op = '[' === $m['l'] ? 'gte' : 'gt';
            $andX->add($queryBuilder->expr()->$op($field, ':'.$paramName));
            $params[$paramName] = FilterExpressionHandler::castValue($m['start'], $castType);
        }

        if ('' !== $m['end']) {
            $paramName = 'filter_range_end_'.$boundCounter++;
            $op = ']' === $m['r'] ? 'lte' : 'lt';
            $andX->add($queryBuilder->expr()->$op($field, ':'.$paramName));
            $params[$paramName] = FilterExpressionHandler::castValue($m['end'], $castType);
        }

        return new EvaluationResult($andX, $params);
    }

    protected static function getExpressionPattern(): string
    {
        return '#^range(?P<l>\[|\])(?P<start>[^,]*),(?P<end>[^,]*)(?P<r>\[|\])$#i';
    }
}
