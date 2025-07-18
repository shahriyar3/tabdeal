<?php

namespace Mautic\LeadBundle\Tests\Event;

use Mautic\LeadBundle\Event\LeadTimelineEvent;

#[\PHPUnit\Framework\Attributes\CoversClass(LeadTimelineEvent::class)]
class LeadTimelineEventTest extends \PHPUnit\Framework\TestCase
{
    #[\PHPUnit\Framework\Attributes\TestDox('Every event in the timeline should have a unique eventId so test that one is generated if the subscriber forgets')]
    public function testEventIdIsGeneratedIfNotSetBySubscriber(): void
    {
        $payload = [
            [
                'event'      => 'foo',
                'eventLabel' => 'Foo',
                'eventType'  => 'foo',
                'timestamp'  => new \DateTime(),
                'extra'      => [
                    'something' => 'something',
                ],
                'icon'      => 'ri-speed-up-line',
                'contactId' => 1,
            ],
            [
                'event'      => 'bar',
                'eventLabel' => 'Bar',
                'eventType'  => 'bar',
                'timestamp'  => new \DateTime(),
                'extra'      => [
                    'something' => 'something else',
                ],
                'icon'      => 'ri-speed-up-line',
                'contactId' => 2,
            ],
            [
                'event'      => 'foobar',
                'eventId'    => 'foobar123',
                'eventLabel' => 'Foo Bar',
                'eventType'  => 'foobar',
                'timestamp'  => new \DateTime(),
                'extra'      => [
                    'something' => 'something else',
                ],
                'icon'      => 'ri-speed-up-line',
                'contactId' => 2,
            ],
        ];

        $event = new LeadTimelineEvent();

        foreach ($payload as $data) {
            $event->addEvent($data);
        }

        $events = $event->getEvents();

        $id1 = hash('crc32', json_encode($payload[0]), false);
        $this->assertTrue(isset($events[0]['eventId']));
        $this->assertEquals('foo'.$id1, $events[0]['eventId']);

        $id2 = hash('crc32', json_encode($payload[1]), false);
        $this->assertTrue(isset($events[1]['eventId']));
        $this->assertEquals('bar'.$id2, $events[1]['eventId']);

        $this->assertTrue(isset($events[2]['eventId']));
        $this->assertEquals('foobar123', $events[2]['eventId']);
    }
}
