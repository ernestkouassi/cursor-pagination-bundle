<?php

declare(strict_types=1);

namespace E2k\CursorPaginationBundle\FilterExpression;

use Doctrine\ORM\QueryBuilder;

/**
 * @author Ernest kOUASSI <ernestkouassi02@gmail.com>
 */
class FilterExpressionHandler
{
    /** @var FilterExpressionInterface[] */
    private array $filterExpressions = [];

    public function __construct(iterable $filterExpressions = [])
    {
        foreach ($filterExpressions as $expr) {
            $this->filterExpressions[] = $expr;
        }
    }

    public function addFilterExpression(FilterExpressionInterface $filterExpression): void
    {
        $this->filterExpressions[] = $filterExpression;
    }

    public function evaluate(QueryBuilder $qb, string $field, mixed $value, string $castType, int &$boundCounter): void
    {
        foreach ($this->filterExpressions as $expr) {
            if (!$expr->supports($qb, $value)) {
                continue;
            }

            $result = $expr->evaluate($qb, $field, $value, $castType, $boundCounter);
            $qb->andWhere($result->getExpr());

            foreach ($result->getParameters() as $name => $val) {
                $qb->setParameter($name, $val);
            }

            return;
        }

        // Default: equality
        $paramName = 'filter_eq_'.$boundCounter++;
        $qb->andWhere($qb->expr()->eq($field, ':'.$paramName));
        $qb->setParameter($paramName, self::castValue($value, $castType));
    }

    public static function castValue(mixed $value, string $castType): mixed
    {
        return match ($castType) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => filter_var($value, \FILTER_VALIDATE_BOOLEAN),
            'datetime' => new \DateTime((string) $value),
            default => (string) $value,
        };
    }
}
