<?php
declare(strict_types=1);

namespace EonX\EasySchedule\Bundle\CompilerPass;

use EonX\EasySchedule\Schedule\ScheduleInterface;
use EonX\EasySchedule\Schedule\TraceableSchedule;
use EonX\EasySchedule\Schedule\TraceableScheduleInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class ScheduleCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $this->registerSchedule($container);
    }

    /**
     * @return \Symfony\Component\DependencyInjection\Reference[]
     */
    private function getProviderReferences(ContainerBuilder $container): array
    {
        $tagged = $container->findTaggedServiceIds('easy_schedule.schedule_provider');

        return \array_map(static fn (string $id): Reference => new Reference($id), \array_keys($tagged));
    }

    private function registerSchedule(ContainerBuilder $container): void
    {
        if ($container->getParameter('kernel.debug')) {
            $container
                ->register(TraceableScheduleInterface::class, TraceableSchedule::class)
                ->setDecoratedService(ScheduleInterface::class)
                ->addArgument(new Reference(\sprintf('%s.inner', TraceableScheduleInterface::class)))
                ->addMethodCall('addProviders', [$this->getProviderReferences($container)]);

            return;
        }

        $schedule = $container->findDefinition(ScheduleInterface::class);
        $schedule->addMethodCall('addProviders', [$this->getProviderReferences($container)]);
    }
}
