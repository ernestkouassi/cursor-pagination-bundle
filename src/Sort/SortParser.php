<?php

declare(strict_types=1);

namespace E2k\CursorPaginationBundle\Sort;

/**
 * Parses sort parameters using oka_pagination-compatible convention: *   sort=createdAt&desc=createdAt → ['createdAt' => 'DESC']
 *   ?sort=createdAt&asc=createdAt →  ['createdAt' => 'ASC']
 *   ?sort=createdAt&sort=amount&desc=createdAt&asc=amount  →  ['createdAt' => 'DESC', 'amount' => 'ASC']
 *
 * @author Ernest kOUASSI <ernestkouassi02@gmail.com>
 */
class SortParser
{
    public function parse(array $queryParams): array
    {
        $sorts = isset($queryParams['sort']) ? (array) $queryParams['sort'] : [];
        $descFields = isset($queryParams['desc']) ? (array) $queryParams['desc'] : [];

        $result = [];
        foreach ($sorts as $field) {
            $result[$field] = in_array($field, $descFields, true) ? 'DESC' : 'ASC';
        }

        return $result;
    }
}
