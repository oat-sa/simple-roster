<?php declare(strict_types=1);

namespace App\DependencyInjection\Compiler;

use App\Ingester\Registry\IngesterSourceRegistry;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;
use App\Ingester\Registry\IngesterRegistry;

class IngesterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $this
            ->registerIngesters($container)
            ->registerSources($container);
    }

    private function registerIngesters(ContainerBuilder $container): self
    {
        if (!$container->has(IngesterRegistry::class)) {
            return $this;
        }

        $definition = $container->findDefinition(IngesterRegistry::class);

        foreach ($container->findTaggedServiceIds('app.ingester') as $id => $tags) {
            $definition->addMethodCall('add', [new Reference($id)]);
        }

        return $this;
    }

    private function registerSources(ContainerBuilder $container): self
    {
        if (!$container->has(IngesterSourceRegistry::class)) {
            return $this;
        }

        $definition = $container->findDefinition(IngesterSourceRegistry::class);

        foreach ($container->findTaggedServiceIds('app.ingester_source') as $id => $tags) {
            $definition->addMethodCall('add', [new Reference($id)]);
        }

        return $this;
    }
}