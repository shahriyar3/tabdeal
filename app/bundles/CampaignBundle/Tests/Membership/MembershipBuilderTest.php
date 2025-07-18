<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Membership;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\LeadRepository as CampaignMemberRepository;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CampaignBundle\Membership\MembershipBuilder;
use Mautic\CampaignBundle\Membership\MembershipManager;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\Translation\TranslatorInterface;

final class MembershipBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MembershipManager|MockObject
     */
    private MockObject $manager;

    /**
     * @var CampaignMemberRepository|MockObject
     */
    private MockObject $campaignMemberRepository;

    /**
     * @var LeadRepository|MockObject
     */
    private MockObject $leadRepository;

    /**
     * @var TranslatorInterface|MockObject
     */
    private MockObject $translator;

    private MembershipBuilder $membershipBuilder;

    protected function setUp(): void
    {
        $this->manager                  = $this->createMock(MembershipManager::class);
        $this->campaignMemberRepository = $this->createMock(CampaignMemberRepository::class);
        $this->leadRepository           = $this->createMock(LeadRepository::class);
        $this->translator               = $this->createMock(TranslatorInterface::class);
        $this->membershipBuilder        = new MembershipBuilder(
            $this->manager,
            $this->campaignMemberRepository,
            $this->leadRepository,
            $this->translator
        );
    }

    public function testContactCountIsSkippedWhenOutputIsNull(): void
    {
        $campaign       = new Campaign();
        $contactLimiter = new ContactLimiter(100);

        $this->campaignMemberRepository->expects($this->never())
            ->method('getCountsForCampaignContactsBySegment');

        $this->campaignMemberRepository->expects($this->never())
            ->method('getCountsForOrphanedContactsBySegments');

        $this->campaignMemberRepository->expects($this->once())
            ->method('getCampaignContactsBySegments')
            ->willReturn([]);

        $this->campaignMemberRepository->expects($this->once())
            ->method('getOrphanedContacts')
            ->willReturn([]);

        $this->membershipBuilder->build($campaign, $contactLimiter, 1000);
    }

    public function testContactsAreNotRemovedIfRunLimitReachedWhileAdding(): void
    {
        $campaign       = new Campaign();
        $contactLimiter = new ContactLimiter(100);

        $this->campaignMemberRepository->expects($this->once())
            ->method('getCampaignContactsBySegments')
            ->willReturn([20, 21, 22]);

        $this->leadRepository->expects($this->once())
            ->method('getContactCollection')
            ->willReturn(new ArrayCollection([new Lead(), new Lead(), new Lead()]));

        $this->campaignMemberRepository->expects($this->never())
            ->method('getOrphanedContacts');

        $this->membershipBuilder->build($campaign, $contactLimiter, 2);
    }

    public function testWhileLoopBreaksWithNoMoreContacts(): void
    {
        $campaign = new class extends Campaign {
            public function getId()
            {
                return 111;
            }
        };

        $contactLimiter = new ContactLimiter(1);
        $matcher        = $this->exactly(4);

        $this->campaignMemberRepository->expects($matcher)
            ->method('getCampaignContactsBySegments')->willReturnCallback(function (...$parameters) use ($matcher, $contactLimiter) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame(111, $parameters[0]);
                    $this->assertSame($contactLimiter, $parameters[1]);
                    $this->assertFalse($parameters[2]);

                    return [20];
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame(111, $parameters[0]);
                    $this->assertSame($contactLimiter, $parameters[1]);
                    $this->assertFalse($parameters[2]);

                    return [21];
                }
                if (3 === $matcher->numberOfInvocations()) {
                    $this->assertSame(111, $parameters[0]);
                    $this->assertSame($contactLimiter, $parameters[1]);
                    $this->assertFalse($parameters[2]);

                    return [22];
                }
                if (4 === $matcher->numberOfInvocations()) {
                    $this->assertSame(111, $parameters[0]);
                    $this->assertSame($contactLimiter, $parameters[1]);
                    $this->assertFalse($parameters[2]);

                    return [];
                }
            });

        $this->manager->expects($this->exactly(3))
            ->method('addContacts');

        $this->campaignMemberRepository->expects($this->exactly(4))
            ->method('getOrphanedContacts')
            ->willReturnOnConsecutiveCalls([23], [24], [25], []);

        $this->manager->expects($this->exactly(3))
            ->method('removeContacts');

        $this->leadRepository->expects($this->exactly(6))
            ->method('getContactCollection')
            ->willReturn(new ArrayCollection([new Lead()]));

        $this->membershipBuilder->build($campaign, $contactLimiter, 100);
    }

    public function testWhileLoopBreaksWithNoMoreContactsForRepeatableCampaign(): void
    {
        $campaign = new class extends Campaign {
            public function getId()
            {
                return 111;
            }
        };

        $campaign->setAllowRestart(true);

        $contactLimiter = new ContactLimiter(1);
        $matcher        = $this->exactly(4);

        $this->campaignMemberRepository->expects($matcher)
            ->method('getCampaignContactsBySegments')->willReturnCallback(function (...$parameters) use ($matcher, $contactLimiter) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame(111, $parameters[0]);
                    $this->assertSame($contactLimiter, $parameters[1]);
                    $this->assertTrue($parameters[2]);

                    return [20];
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame(111, $parameters[0]);
                    $this->assertSame($contactLimiter, $parameters[1]);
                    $this->assertTrue($parameters[2]);

                    return [21];
                }
                if (3 === $matcher->numberOfInvocations()) {
                    $this->assertSame(111, $parameters[0]);
                    $this->assertSame($contactLimiter, $parameters[1]);
                    $this->assertTrue($parameters[2]);

                    return [22];
                }
                if (4 === $matcher->numberOfInvocations()) {
                    $this->assertSame(111, $parameters[0]);
                    $this->assertSame($contactLimiter, $parameters[1]);
                    $this->assertTrue($parameters[2]);

                    return [];
                }
            });

        $this->manager->expects($this->exactly(3))
            ->method('addContacts');

        $this->campaignMemberRepository->expects($this->exactly(4))
            ->method('getOrphanedContacts')
            ->willReturnOnConsecutiveCalls([23], [24], [25], []);

        $this->manager->expects($this->exactly(3))
            ->method('removeContacts');

        $this->leadRepository->expects($this->exactly(6))
            ->method('getContactCollection')
            ->willReturn(new ArrayCollection([new Lead()]));

        $this->membershipBuilder->build($campaign, $contactLimiter, 100);
    }
}
