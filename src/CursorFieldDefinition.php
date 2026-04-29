<?php

declare(strict_types=1);

namespace E2k\CursorPaginationBundle;

/**
 * Describes a field used to position the cursor (keyset pagination).
 *
 * propertyName is used by PropertyAccess to read the value from the entity.
 * dqlPath is used in the WHERE/ORDER BY DQL clause.
 *
 * @author Ernest kOUASSI <ernestkouassi02@gmail.com>
 */
final class CursorFieldDefinition
{
    public function __construct(
        public readonly string $propertyName,
        public readonly string $dqlPath,
        public readonly string $castType = 'string',
    ) {
    }
}
