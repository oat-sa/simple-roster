<?php declare(strict_types=1);

namespace App\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;
use App\Ingester\Registry\IngesterRegistry;

class IngesterRegistryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(IngesterRegistry::class)) {
            return;
        }

        $definition = $container->findDefinition(IngesterRegistry::class);

        foreach ($container->findTaggedServiceIds('app.ingester') as $id => $tags) {
            $definition->addMethodCall('add', [new Reference($id)]);
        }
    }
}