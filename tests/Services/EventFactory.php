<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Entity\Event;
use App\Entity\Reference;
use App\EntityFactory\ReferenceFactory;
use App\Repository\EventRepository;
use webignition\ObjectReflector\ObjectReflector;

class EventFactory
{
    public function __construct(
        private readonly ReferenceFactory $referenceFactory,
        private readonly EventRepository $eventRepository,
    ) {
    }

    public function persist(Event $event): void
    {
        $eventReference = ObjectReflector::getProperty($event, 'reference');
        \assert($eventReference instanceof Reference);

        $referenceLabel = ObjectReflector::getProperty($eventReference, 'label');
        \assert(is_string($referenceLabel));
        \assert('' !== $referenceLabel);

        $referenceReference = ObjectReflector::getProperty($eventReference, 'reference');
        \assert(is_string($referenceReference));
        \assert('' !== $referenceReference);

        $referenceEntity = $this->referenceFactory->create($referenceLabel, $referenceReference);

        $event = $this->createEventWithReference($event, $referenceEntity);

        $this->eventRepository->add($event);
    }

    private function createEventWithReference(Event $event, Reference $reference): Event
    {
        $reflectionClass = new \ReflectionClass($event);
        $reflectionEvent = $reflectionClass->newInstanceWithoutConstructor();
        \assert($reflectionEvent instanceof Event);

        $referenceProperty = $reflectionClass->getProperty('reference');
        $referenceProperty->setValue($reflectionEvent, $reference);

        $propertyNames = ['id', 'sequenceNumber', 'job', 'type', 'body', 'relatedReferences'];
        foreach ($propertyNames as $propertyName) {
            $property = $reflectionClass->getProperty($propertyName);
            $property->setValue($reflectionEvent, ObjectReflector::getProperty($event, $propertyName));
        }

        return $reflectionEvent;
    }
}
