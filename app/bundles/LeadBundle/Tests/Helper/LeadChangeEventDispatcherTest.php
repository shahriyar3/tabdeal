<?php

namespace Mautic\LeadBundle\Tests\Helper;

use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event\ChannelSubscriptionChange;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\Event\LeadUtmTagsEvent;
use Mautic\LeadBundle\Event\PointsChangeEvent;
use Mautic\LeadBundle\Helper\LeadChangeEventDispatcher;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventDispatcher;

class LeadChangeEventDispatcherTest extends \PHPUnit\Framework\TestCase
{
    #[\PHPUnit\Framework\Attributes\TestDox('Test that date identified change dispatches correct event')]
    public function testDateIdentifiedEventIsDispatched(): void
    {
        $dispatcher = $this->createMock(EventDispatcher::class);

        $lead  = new Lead();
        $event = new LeadEvent($lead);

        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $event,
                LeadEvents::LEAD_IDENTIFIED
            );

        $leadEventDispatcher = new LeadChangeEventDispatcher($dispatcher);

        $leadEventDispatcher->dispatchEvents($event, ['dateIdentified' => ['foo', 'bar']]);
    }

    #[\PHPUnit\Framework\Attributes\TestDox('Test that point changes dispatches correct event')]
    public function testPointChangeEventIsDispatched(): void
    {
        $dispatcher = $this->createMock(EventDispatcher::class);

        $lead        = new Lead();
        $event       = new LeadEvent($lead);
        $pointsEvent = new PointsChangeEvent($lead, 10, 20);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $pointsEvent,
                LeadEvents::LEAD_POINTS_CHANGE
            );

        $leadEventDispatcher = new LeadChangeEventDispatcher($dispatcher);

        $leadEventDispatcher->dispatchEvents($event, ['points' => [10, 20]]);
    }

    #[\PHPUnit\Framework\Attributes\TestDox('Test that points change event is not dispatched if we did an import')]
    public function testPointChangeEventIsNotDispatchedWithImport(): void
    {
        $dispatcher = $this->createMock(EventDispatcher::class);

        $lead           = new Lead();
        $lead->imported = true;

        $event = new LeadEvent($lead);

        $dispatcher->expects($this->never())
            ->method('dispatch');

        $leadEventDispatcher = new LeadChangeEventDispatcher($dispatcher);

        $leadEventDispatcher->dispatchEvents($event, ['points' => [10, 20]]);
    }

    #[\PHPUnit\Framework\Attributes\TestDox('Test that points change event is not dispatched if points are empty (false positive)')]
    public function testPointChangeEventIsNotDispatchedWithEmptyPoints(): void
    {
        $dispatcher = $this->createMock(EventDispatcher::class);

        $lead  = new Lead();
        $event = new LeadEvent($lead);

        $dispatcher->expects($this->never())
            ->method('dispatch');

        $leadEventDispatcher = new LeadChangeEventDispatcher($dispatcher);

        $leadEventDispatcher->dispatchEvents($event, ['points' => [0, 0]]);
    }

    #[\PHPUnit\Framework\Attributes\TestDox('Test that points change event is dispatched if points are changed from something to nothing')]
    public function testPointChangeEventIsDispatchedWithPointsChangedToZero(): void
    {
        $dispatcher = $this->createMock(EventDispatcher::class);

        $lead        = new Lead();
        $event       = new LeadEvent($lead);
        $pointsEvent = new PointsChangeEvent($lead, 10, 0);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $pointsEvent,
                LeadEvents::LEAD_POINTS_CHANGE
            );

        $leadEventDispatcher = new LeadChangeEventDispatcher($dispatcher);

        $leadEventDispatcher->dispatchEvents($event, ['points' => [10, 0]]);
    }

    #[\PHPUnit\Framework\Attributes\TestDox('Test that points change event is not dispatched if this is a new Lead')]
    public function testPointChangeEventIsNotDispatchedWithNewContact(): void
    {
        $dispatcher = $this->createMock(EventDispatcher::class);

        $lead  = new Lead();
        $event = new LeadEvent($lead, true);
        $dispatcher->expects($this->never())
            ->method('dispatch');

        $leadEventDispatcher = new LeadChangeEventDispatcher($dispatcher);

        $leadEventDispatcher->dispatchEvents($event, ['points' => [10, 0]]);
    }

    #[\PHPUnit\Framework\Attributes\TestDox('Test that utm event is dispatched')]
    public function testUtmTagsChangeEventIsDispatched(): void
    {
        $dispatcher = $this->createMock(EventDispatcher::class);

        $lead         = new Lead();
        $event        = new LeadEvent($lead);
        $changes      = ['utmtags' => ['foo', 'bar']];
        $utmTagsEvent = new LeadUtmTagsEvent($lead, $changes['utmtags']);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $utmTagsEvent,
                LeadEvents::LEAD_UTMTAGS_ADD
            );

        $leadEventDispatcher = new LeadChangeEventDispatcher($dispatcher);

        $leadEventDispatcher->dispatchEvents($event, $changes);
    }

    #[\PHPUnit\Framework\Attributes\TestDox('Test that channel subscription changes are dispatched')]
    public function testChannelSubscriptionChangeEventIsDispatched(): void
    {
        $dispatcher = $this->createMock(EventDispatcher::class);

        $lead    = new Lead();
        $event   = new LeadEvent($lead);
        $changes = ['dnc_channel_status' => ['email' => ['old_reason' => DoNotContact::IS_CONTACTABLE, 'reason' => DoNotContact::UNSUBSCRIBED]]];

        $dncEvent = new ChannelSubscriptionChange($lead, 'email', DoNotContact::IS_CONTACTABLE, DoNotContact::UNSUBSCRIBED);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $dncEvent,
                LeadEvents::CHANNEL_SUBSCRIPTION_CHANGED
            );

        $leadEventDispatcher = new LeadChangeEventDispatcher($dispatcher);

        $leadEventDispatcher->dispatchEvents($event, $changes);
    }
}
