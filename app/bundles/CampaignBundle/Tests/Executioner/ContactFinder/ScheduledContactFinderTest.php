<?php

namespace Mautic\CampaignBundle\Tests\Executioner\ContactFinder;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Executioner\ContactFinder\ScheduledContactFinder;
use Mautic\CampaignBundle\Executioner\Exception\NoContactsFoundException;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Psr\Log\NullLogger;

class ScheduledContactFinderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LeadRepository
     */
    private \PHPUnit\Framework\MockObject\MockObject $leadRepository;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|CampaignRepository
     */
    private \PHPUnit\Framework\MockObject\MockObject $campaignRepository;

    protected function setUp(): void
    {
        $this->leadRepository = $this->createMock(LeadRepository::class);

        $this->campaignRepository = $this->createMock(CampaignRepository::class);
    }

    public function testHydratedLeadsFromRepositoryAreFoundAndPushedIntoLogs(): void
    {
        $lead1 = $this->createMock(Lead::class);
        $lead1->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(1);

        $lead2 = $this->createMock(Lead::class);
        $lead2->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(2);

        $log1 = $this->createMock(LeadEventLog::class);
        $log1->expects($this->exactly(2))
            ->method('getLead')
            ->willReturn($lead1);
        $log1->expects($this->once())
            ->method('setLead');

        $log2 = $this->createMock(LeadEventLog::class);
        $log2->expects($this->exactly(2))
            ->method('getLead')
            ->willReturn($lead2);
        $log2->expects($this->once())
            ->method('setLead');

        $logs = new ArrayCollection(
            [
                1 => $log1,
                2 => $log2,
            ]
        );

        $contacs = new ArrayCollection(
            [
                1 => $lead1,
                2 => $lead2,
            ]
        );

        $this->leadRepository->expects($this->once())
            ->method('getContactCollection')
            ->willReturn($contacs);

        $this->getContactFinder()->hydrateContacts($logs);
    }

    public function testHydratedLeadsFromRepositoryWithMissingLeadResultsLogBeingRemoved(): void
    {
        $lead1 = $this->createMock(Lead::class);
        $lead1->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(1);

        $lead2 = $this->createMock(Lead::class);
        $lead2->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(2);

        $log1 = $this->createMock(LeadEventLog::class);
        $log1->expects($this->exactly(2))
            ->method('getLead')
            ->willReturn($lead1);
        $log1->expects($this->once())
            ->method('setLead');

        $log2 = $this->createMock(LeadEventLog::class);
        $log2->expects($this->exactly(2))
            ->method('getLead')
            ->willReturn($lead2);
        $log2->expects($this->never())
            ->method('setLead');

        $logs = new ArrayCollection(
            [
                1 => $log1,
                2 => $log2,
            ]
        );

        $contacs = new ArrayCollection(
            [
                1 => $lead1,
            ]
        );

        $this->leadRepository->expects($this->once())
            ->method('getContactCollection')
            ->willReturn($contacs);

        $this->getContactFinder()->hydrateContacts($logs);

        $this->assertCount(1, $logs);
    }

    public function testNoContactsFoundExceptionIsThrownIfEntitiesAreNotFound(): void
    {
        $this->leadRepository->expects($this->never())
            ->method('getContactCollection');

        $this->expectException(NoContactsFoundException::class);

        $this->getContactFinder()->hydrateContacts(new ArrayCollection());
    }

    /**
     * @return ScheduledContactFinder
     */
    private function getContactFinder()
    {
        return new ScheduledContactFinder(
            $this->leadRepository,
            new NullLogger()
        );
    }
}
