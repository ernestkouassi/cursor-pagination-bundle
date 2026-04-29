<?php

declare(strict_types=1);

namespace E2k\CursorPaginationBundle\Pagination;

use E2k\CursorPaginationBundle\Exception\InvalidCursorException;

/**
 * @author Ernest kOUASSI <ernestkouassi02@gmail.com>
 */
class CursorEncoder
{
    public function encode(array $data): string
    {
        return base64_encode(json_encode($data, \JSON_THROW_ON_ERROR));
    }

    public function decode(string $cursor): array
    {
        $decoded = base64_decode($cursor, strict: true);

        if (false === $decoded) {
            throw new InvalidCursorException('Cursor is not valid base64.');
        }

        try {
            $data = json_decode($decoded, associative: true, flags: \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new InvalidCursorException('Cursor contains invalid JSON.');
        }

        if (!is_array($data)) {
            throw new InvalidCursorException('Cursor payload must be a JSON object.');
        }

        return $data;
    }
}
