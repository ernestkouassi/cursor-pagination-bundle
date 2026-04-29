<?php

declare(strict_types=1);

namespace E2k\CursorPaginationBundle\DependencyInjection;

use E2k\CursorPaginationBundle\FilterExpression\FilterExpressionHandler;
use E2k\CursorPaginationBundle\FilterExpression\FilterExpressionInterface;
use E2k\CursorPaginationBundle\FilterExpression\ORM\GtFilterExpression;
use E2k\CursorPaginationBundle\FilterExpression\ORM\GteFilterExpression;
use E2k\CursorPaginationBundle\FilterExpression\ORM\InFilterExpression;
use E2k\CursorPaginationBundle\FilterExpression\ORM\LikeFilterExpression;
use E2k\CursorPaginationBundle\FilterExpression\ORM\LtFilterExpression;
use E2k\CursorPaginationBundle\FilterExpression\ORM\LteFilterExpression;
use E2k\CursorPaginationBundle\FilterExpression\ORM\NeqFilterExpression;
use E2k\CursorPaginationBundle\FilterExpression\ORM\RangeFilterExpression;
use E2k\CursorPaginationBundle\Pagination\CursorEncoder;
use E2k\CursorPaginationBundle\Pagination\CursorQueryFactory;
use E2k\CursorPaginationBundle\Sort\SortParser;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * @author Ernest kOUASSI <ernestkouassi02@gmail.com>
 */
class CursorPaginationExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        // Allow custom filter expressions to be tagged and auto-collected
        $container->registerForAutoconfiguration(FilterExpressionInterface::class)
            ->addTag('e2k.cursor_pagination.filter_expression');

        // Built-in ORM filter expressions
        $builtInExpressions = [
            NeqFilterExpression::class,
            LikeFilterExpression::class,
            InFilterExpression::class,
            RangeFilterExpression::class,
            GtFilterExpression::class,
            GteFilterExpression::class,
            LtFilterExpression::class,
            LteFilterExpression::class,
        ];

        $expressionRefs = [];
        foreach ($builtInExpressions as $class) {
            $id = 'e2k.cursor_pagination.filter_expression.'.strtolower((new \ReflectionClass($class))->getShortName());
            $container->setDefinition($id, new Definition($class));
            $expressionRefs[] = new Reference($id);
        }

        // FilterExpressionHandler
        $container->setDefinition(FilterExpressionHandler::class, new Definition(FilterExpressionHandler::class, [$expressionRefs]));

        // CursorEncoder
        $container->setDefinition(CursorEncoder::class, new Definition(CursorEncoder::class));

        // SortParser
        $container->setDefinition(SortParser::class, new Definition(SortParser::class));

        // PropertyAccessor
        $container->setDefinition(PropertyAccessor::class, (new Definition(PropertyAccessor::class))
            ->setFactory([PropertyAccess::class, 'createPropertyAccessor']));

        // CursorQueryFactory
        $container->setDefinition(CursorQueryFactory::class, new Definition(CursorQueryFactory::class, [
            new Reference('doctrine.orm.entity_manager'),
            new Reference(FilterExpressionHandler::class),
            new Reference(CursorEncoder::class),
            new Reference(SortParser::class),
            new Reference(PropertyAccessor::class),
        ]));
    }
}
