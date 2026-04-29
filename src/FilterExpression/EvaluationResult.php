<?php

declare(strict_types=1);

namespace E2k\CursorPaginationBundle\FilterExpression;

/**
 * @author Ernest kOUASSI <ernestkouassi02@gmail.com>
 */
final class EvaluationResult
{
    public function __construct(
        private readonly mixed $expr,
        private readonly array $parameters = [],
    ) {
    }

    public function getExpr(): mixed
    {
        return $this->expr;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}
