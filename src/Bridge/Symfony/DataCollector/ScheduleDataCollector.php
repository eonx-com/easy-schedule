<?php
declare(strict_types=1);

namespace EonX\EasySchedule\Bridge\Symfony\DataCollector;

use EonX\EasySchedule\Bridge\Symfony\Interfaces\TraceableScheduleInterface;
use EonX\EasySchedule\Interfaces\ScheduleInterface;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Throwable;

final class ScheduleDataCollector extends DataCollector
{
    public const NAME = 'schedule.schedule_collector';

    public function __construct(
        private ScheduleInterface $schedule,
    ) {
    }

    public function collect(Request $request, Response $response, ?Throwable $exception = null): void
    {
        if (($this->schedule instanceof TraceableScheduleInterface) === false) {
            return;
        }

        $this->data['providers'] = [];
        $this->data['events'] = [];

        foreach ($this->schedule->getProviders() as $provider) {
            $class = $provider::class;

            $this->data['providers'][$class] = [
                'class' => $class,
                'events_count' => 0,
                'file' => (new ReflectionClass($class))->getFileName(),
            ];
        }

        foreach ($this->schedule->getEvents() as $provider => $events) {
            $this->data['providers'][$provider]['events_count'] = \count($events);

            foreach ($events as $event) {
                $this->data['events'][] = [
                    'allowsOverlapping' => $event->allowsOverlapping(),
                    'cronExpression' => $event->getCronExpression(),
                    'description' => $event->getDescription(),
                    'lockResource' => $event->getLockResource(),
                    'maxLockTime' => $event->getMaxLockTime(),
                    'provider' => $this->data['providers'][$provider],
                    'timezone' => $event->getTimezone(),
                ];
            }
        }
    }

    public function getEvents(): array
    {
        return $this->data['events'] ?? [];
    }

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @return string[]
     */
    public function getProviders(): array
    {
        return $this->data['providers'] ?? [];
    }

    public function reset(): void
    {
        $this->data = [];
    }
}
