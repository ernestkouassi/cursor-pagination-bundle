<?php

declare(strict_types=1);

namespace E2k\CursorPaginationBundle;

/**
 * Describes a field that can be used as a filter in query params.
 *
 * @author Ernest kOUASSI <ernestkouassi02@gmail.com>
 */
final class FieldDefinition
{
    public function __construct(
        public readonly string $paramName,
        public readonly string $dqlPath,
        public readonly string $castType = 'string',
    ) {
    }
}
