<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Event\DetermineWinnerEvent;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\EventListener\DetermineWinnerSubscriber;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\HitRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\Translation\TranslatorInterface;

class DetermineWinnerSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|EntityManagerInterface
     */
    private MockObject $em;

    /**
     * @var MockObject|TranslatorInterface
     */
    private MockObject $translator;

    private DetermineWinnerSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->em         = $this->createMock(EntityManagerInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->subscriber = new DetermineWinnerSubscriber($this->em, $this->translator);
    }

    public function testOnDetermineOpenRateWinner(): void
    {
        $parentMock = $this->createMock(Email::class);
        $children   = [2 => $this->createMock(Email::class)];
        $repoMock   = $this->createMock(StatRepository::class);
        $ids        = [1, 2];
        $parameters = ['parent' => $parentMock, 'children' => $children];
        $event      = new DetermineWinnerEvent($parameters);
        $startDate  = new \DateTime();

        $openedRates = [
            1 => [
                'totalCount' => 5,
                'readCount'  => 0,
                'readRate'   => 0,
            ],
            2 => [
                'totalCount' => 6,
                'readCount'  => 3,
                'readRate'   => 50,
            ],
        ];
        $this->translator->method('trans')->willReturnMap([
            ['mautic.email.abtest.label.opened', [], null, null, 'opened'],
            ['mautic.email.abtest.label.sent', [], null, null, 'sent'],
        ]);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repoMock);

        $parentMock->expects($this->once())
            ->method('getRelatedEntityIds')
            ->willReturn($ids);

        $parentMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $parentMock->expects($this->once())
            ->method('getVariantStartDate')
            ->willReturn($startDate);

        $repoMock->expects($this->once())
            ->method('getOpenedRates')
            ->with($ids, $startDate)
            ->willReturn($openedRates);

        $this->subscriber->onDetermineOpenRateWinner($event);

        $expectedData = [
            'opened' => [$openedRates[1]['readCount'], $openedRates[2]['readCount']],
            'sent'   => [$openedRates[1]['totalCount'], $openedRates[2]['totalCount']],
        ];

        $abTestResults = $event->getAbTestResults();

        $this->assertEquals($abTestResults['winners'], [2]);
        $this->assertEquals($abTestResults['support']['data'], $expectedData);
    }

    public function testOnDetermineOClickthroughRateWinner(): void
    {
        $parentMock    = $this->createMock(Email::class);
        $children      = [2 => $this->createMock(Email::class)];
        $pageRepoMock  = $this->createMock(HitRepository::class);
        $emailRepoMock = $this->createMock(StatRepository::class);
        $ids           = [1, 2];
        $parameters    = ['parent' => $parentMock, 'children' => $children];
        $event         = new DetermineWinnerEvent($parameters);
        $startDate     = new \DateTime();

        $clickthroughCounts = [
            1 => 41,
            2 => 62,
        ];

        $sentCounts = [
            1 => 168,
            2 => 153,
        ];

        $this->translator->method('trans')->willReturnMap(
            [
                ['mautic.email.abtest.label.clickthrough', [], null, null, 'clickthrough'],
                ['mautic.email.abtest.label.opened', [], null, null, 'opened'],
            ]
        );

        $matcher = $this->exactly(2);

        $this->em->expects($matcher)->method('getRepository')->willReturnCallback(function (...$parameters) use ($matcher, $pageRepoMock, $emailRepoMock) {
            if (1 === $matcher->numberOfInvocations()) {
                $this->assertSame(Hit::class, $parameters[0]);

                return $pageRepoMock;
            }
            if (2 === $matcher->numberOfInvocations()) {
                $this->assertSame(Stat::class, $parameters[0]);

                return $emailRepoMock;
            }
        });

        $parentMock->expects($this->once())
            ->method('getRelatedEntityIds')
            ->willReturn($ids);

        $parentMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $parentMock->expects($this->once())
            ->method('getVariantStartDate')
            ->willReturn($startDate);

        $pageRepoMock->expects($this->once())
            ->method('getEmailClickthroughHitCount')
            ->with($ids, $startDate)
            ->willReturn($clickthroughCounts);

        $emailRepoMock->expects($this->once())
            ->method('getSentCounts')
            ->with($ids, $startDate)
            ->willReturn($sentCounts);

        $this->subscriber->onDetermineClickthroughRateWinner($event);

        $expectedData = [
            'opened'       => [$sentCounts[1], $sentCounts[2]],
            'clickthrough' => [$clickthroughCounts[1], $clickthroughCounts[2]],
        ];

        $abTestResults = $event->getAbTestResults();

        $this->assertEquals($abTestResults['winners'], [2]);
        $this->assertEquals($abTestResults['support']['data'], $expectedData);
    }
}
