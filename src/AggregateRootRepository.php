<?php

namespace Spatie\LaravelEventSauce;

use EventSauce\EventSourcing\Consumer;
use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\MessageDecorator;
use EventSauce\EventSourcing\MessageDispatcherChain;
use EventSauce\EventSourcing\SynchronousMessageDispatcher;
use  EventSauce\EventSourcing\ConstructingAggregateRootRepository;
use EventSauce\EventSourcing\AggregateRootRepository as EventSauceAggregateRootRepository;

class AggregateRootRepository implements EventSauceAggregateRootRepository
{
    /** @var \EventSauce\EventSourcing\ConstructingAggregateRootRepository */
    protected $constructingAggregateRootRepository;

    public function __construct()
    {
        $this->constructingAggregateRootRepository = new ConstructingAggregateRootRepository(
            $this->getAggregateRootClass(),
            $this->getMessageRepository(),
            new MessageDispatcherChain(
                new QueuedMessageDispatcher(...$this->instanciate($this->getQueuedConsumers())),
                new SynchronousMessageDispatcher(...$this->instanciate($this->getConsumers()))
            ),
            $this->getMessageDecorator()
        );
    }

    public function retrieve(AggregateRootId $aggregateRootId): object
    {
        return $this->constructingAggregateRootRepository->retrieve($aggregateRootId);
    }

    public function persist(object $aggregateRoot)
    {
        return $this->constructingAggregateRootRepository->persist($aggregateRoot);
    }

    public function persistEvents(AggregateRootId $aggregateRootId, int $aggregateRootVersion, object ...$events)
    {
        $this->constructingAggregateRootRepository->persistEvents($aggregateRootId, $aggregateRootVersion);
    }

    protected function getAggregateRootClass(): string
    {
        return static::$aggregateRoot;
    }

    protected function getMessageRepository(): \EventSauce\EventSourcing\MessageRepository
    {
        return app(MessageRepository::class);
    }

    protected function getConsumers(): array
    {
        return static::$consumers;
    }

    protected function getQueuedConsumers(): array
    {
        return static::$queuedConsumers;
    }

    protected function getMessageDecorator(): ?MessageDecorator
    {
        return null;
    }

    protected function instanciate(array $classes): array
    {
        return array_map(function ($class): Consumer {
            return is_string($class)
                ? app($class)
                : $class;
        }, $classes);
    }
}