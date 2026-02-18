<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use SmartAssert\ResultsClient\Client as ResultsClient;
use SmartAssert\ResultsClient\Model\Event;
use SmartAssert\ResultsClient\Model\Job;
use SmartAssert\ResultsClient\Model\JobEventInterface;
use SmartAssert\ResultsClient\Model\JobState;
use SmartAssert\ResultsClient\Model\MetaState;
use SmartAssert\ResultsClient\Model\ResourceReference;
use SmartAssert\ResultsClient\Model\ResourceReferenceCollection;
use SmartAssert\TestAuthenticationProviderBundle\ApiTokenProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Ulid;

class ClientTest extends WebTestCase
{
    /**
     * @var non-empty-string
     */
    private static string $apiToken;

    /**
     * @var non-empty-string
     */
    private static string $jobLabel;

    private static ResultsClient $resultsClient;

    private static Job $job;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::createClient();

        $apiTokenProvider = self::getContainer()->get(ApiTokenProvider::class);
        \assert($apiTokenProvider instanceof ApiTokenProvider);

        self::$apiToken = $apiTokenProvider->get('user@example.com');
        self::$jobLabel = (string) new Ulid();

        $resultsClient = self::getContainer()->get(ResultsClient::class);
        \assert($resultsClient instanceof ResultsClient);
        self::$resultsClient = $resultsClient;

        self::$job = self::$resultsClient->createJob(self::$apiToken, self::$jobLabel);
    }

    public function testCreateJob(): void
    {
        self::assertSame(self::$jobLabel, self::$job->label);
        self::assertEquals(
            new JobState('awaiting-events', null, new MetaState(false, false)),
            self::$job->state
        );
    }

    public function testGetJob(): void
    {
        $jobStatus = self::$resultsClient->getJobStatus(self::$apiToken, self::$jobLabel);
        self::assertEquals(
            new JobState('awaiting-events', null, new MetaState(false, false)),
            $jobStatus
        );
    }

    public function testAddEventListEvents(): void
    {
        $reference1 = md5('test1.yml');
        $reference2 = md5('test2.yml');
        $reference3 = md5('test3.yml');

        $resourceReference1 = new ResourceReference('test1.yml', $reference1);
        $resourceReference2 = new ResourceReference('test2.yml', $reference2);
        $resourceReference3 = new ResourceReference('test3.yml', $reference3);

        $event1 = new Event(
            1,
            'type_1',
            $resourceReference1,
            [
                'key3' => 'value3',
                'key4' => 'value4',
            ],
        );

        $event1 = $event1->withRelatedReferences(
            new ResourceReferenceCollection([
                $resourceReference3,
            ])
        );

        $event2 = self::$resultsClient->addEvent(
            self::$job->token,
            new Event(3, 'type_1', $resourceReference2, [])
        );

        $event3 = self::$resultsClient->addEvent(
            self::$job->token,
            new Event(4, 'type_1', $resourceReference2, [])
        );

        $event4 = self::$resultsClient->addEvent(
            self::$job->token,
            new Event(5, 'type_2', $resourceReference2, [])
        );

        $expectedStoredEvent1 = clone $event1;
        if ($expectedStoredEvent1 instanceof JobEventInterface) {
            $expectedStoredEvent1 = $expectedStoredEvent1->withJob(self::$jobLabel);
        }

        $storedEvent = self::$resultsClient->addEvent(self::$job->token, $event1);
        self::assertEquals($expectedStoredEvent1, $storedEvent);

        $allEvents = self::$resultsClient->listEvents(self::$apiToken, self::$jobLabel, null, null);
        self::assertEquals([$expectedStoredEvent1, $event2, $event3, $event4], $allEvents);

        $reference1Events = self::$resultsClient->listEvents(self::$apiToken, self::$jobLabel, $reference1, null);
        self::assertEquals([$expectedStoredEvent1], $reference1Events);

        $reference2Events = self::$resultsClient->listEvents(self::$apiToken, self::$jobLabel, $reference2, null);
        self::assertEquals([$event2, $event3, $event4], $reference2Events);

        $type1Events = self::$resultsClient->listEvents(self::$apiToken, self::$jobLabel, null, 'type_1');
        self::assertEquals([$expectedStoredEvent1, $event2, $event3], $type1Events);

        $type2Events = self::$resultsClient->listEvents(self::$apiToken, self::$jobLabel, null, 'type_2');
        self::assertEquals([$event4], $type2Events);
    }
}
