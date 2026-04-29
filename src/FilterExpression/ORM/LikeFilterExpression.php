<?php

declare(strict_types=1);

namespace E2k\CursorPaginationBundle\FilterExpression\ORM;

use E2k\CursorPaginationBundle\FilterExpression\AbstractFilterExpression;
use E2k\CursorPaginationBundle\FilterExpression\EvaluationResult;

/**
 * Handles:
 *   like(bob) → LIKE '%bob%' (auto-wrap when no % present)
 *   like(bob%) → LIKE 'bob%'
 *   like(%bob) → LIKE '%bob'
 *   like(%bob%) → LIKE '%bob%'
 *
 * @author Ernest kOUASSI <ernestkouassi02@gmail.com>
 */
class LikeFilterExpression extends AbstractFilterExpression
{
    public function evaluate(object $queryBuilder, string $field, mixed $value, string $castType, int &$boundCounter): EvaluationResult
    {
        preg_match(self::getExpressionPattern(), $value, $matches);
        $pattern = $matches[1];

        if (!str_contains($pattern, '%')) {
            $pattern = '%'.$pattern.'%';
        }

        $paramName = 'filter_like_'.$boundCounter++;

        return new EvaluationResult(
            $queryBuilder->expr()->like($field, ':'.$paramName),
            [$paramName => $pattern],
        );
    }

    protected static function getExpressionPattern(): string
    {
        return '#^like\((.+)\)$#i';
    }
}
