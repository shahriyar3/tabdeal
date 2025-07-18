<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Sync\Notification\Helper;

use Mautic\IntegrationsBundle\Sync\Notification\Helper\OwnerProvider;
use Mautic\IntegrationsBundle\Sync\Notification\Helper\RouteHelper;
use Mautic\IntegrationsBundle\Sync\Notification\Helper\UserHelper;
use Mautic\IntegrationsBundle\Sync\Notification\Helper\UserSummaryNotificationHelper;
use Mautic\IntegrationsBundle\Sync\Notification\Writer;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserSummaryNotificationHelperTest extends TestCase
{
    /**
     * @var Writer|MockObject
     */
    private MockObject $writer;

    /**
     * @var UserHelper|MockObject
     */
    private MockObject $userHelper;

    /**
     * @var OwnerProvider|MockObject
     */
    private MockObject $ownerProvider;

    /**
     * @var RouteHelper|MockObject
     */
    private MockObject $routeHelper;

    /**
     * @var TranslatorInterface|MockObject
     */
    private MockObject $translator;

    private UserSummaryNotificationHelper $helper;

    protected function setUp(): void
    {
        $this->writer        = $this->createMock(Writer::class);
        $this->userHelper    = $this->createMock(UserHelper::class);
        $this->ownerProvider = $this->createMock(OwnerProvider::class);
        $this->routeHelper   = $this->createMock(RouteHelper::class);
        $this->translator    = $this->createMock(TranslatorInterface::class);
        $this->helper        = new UserSummaryNotificationHelper(
            $this->writer,
            $this->userHelper,
            $this->ownerProvider,
            $this->routeHelper,
            $this->translator
        );
    }

    public function testNotificationSentToOwner(): void
    {
        $this->helper->storeSummaryNotification('Foo', 'Bar', 1);
        $this->helper->storeSummaryNotification('Bar', 'Foo', 2);
        $matcher = $this->exactly(2);

        $this->ownerProvider->expects($matcher)
            ->method('getOwnersForObjectIds')->willReturnCallback(function (...$parameters) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame(Contact::NAME, $parameters[0]);
                    $this->assertSame([1 => 1], $parameters[1]);

                    return [['owner_id' => 1, 'id' => 1]];
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame(Contact::NAME, $parameters[0]);
                    $this->assertSame([2 => 2], $parameters[1]);

                    return [['owner_id' => 2, 'id' => 2]];
                }
            });

        $this->userHelper->expects($this->never())
            ->method('getAdminUsers');
        $matcher = $this->exactly(4);

        $this->translator->expects($matcher)
            ->method('trans')->willReturnCallback(function (...$parameters) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('mautic.integration.sync.user_notification.header', $parameters[0]);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame('test', $parameters[0]);
                }
                if (3 === $matcher->numberOfInvocations()) {
                    $this->assertSame('mautic.integration.sync.user_notification.header', $parameters[0]);
                }
                if (4 === $matcher->numberOfInvocations()) {
                    $this->assertSame('test', $parameters[0]);
                }

                return 'test';
            });

        $this->writer->expects($this->exactly(2))
            ->method('writeUserNotification');

        $this->routeHelper->expects($this->exactly(2))
            ->method('getLinkCsv');

        $this->helper->writeNotifications(Contact::NAME, 'test');
    }

    public function testNotificationSentToAdmins(): void
    {
        $this->helper->storeSummaryNotification('Foo', 'Bar', 1);
        $this->helper->storeSummaryNotification('Bar', 'Foo', 2);
        $matcher = $this->exactly(2);

        $this->ownerProvider->expects($matcher)
            ->method('getOwnersForObjectIds')->willReturnCallback(function (...$parameters) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame(Contact::NAME, $parameters[0]);
                    $this->assertSame([1 => 1], $parameters[1]);

                    return [];
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame(Contact::NAME, $parameters[0]);
                    $this->assertSame([2 => 2], $parameters[1]);

                    return [];
                }
            });

        $this->userHelper->expects($this->exactly(2))
            ->method('getAdminUsers')
            ->willReturn([1]);
        $matcher = $this->exactly(4);

        $this->translator->expects($matcher)
            ->method('trans')->willReturnCallback(function (...$parameters) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('mautic.integration.sync.user_notification.header', $parameters[0]);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame('test', $parameters[0]);
                }
                if (3 === $matcher->numberOfInvocations()) {
                    $this->assertSame('mautic.integration.sync.user_notification.header', $parameters[0]);
                }
                if (4 === $matcher->numberOfInvocations()) {
                    $this->assertSame('test', $parameters[0]);
                }

                return 'test';
            });

        $this->writer->expects($this->exactly(2))
            ->method('writeUserNotification');

        $this->routeHelper->expects($this->exactly(2))
            ->method('getLinkCsv');

        $this->helper->writeNotifications(Contact::NAME, 'test');
    }

    public function testMoreThan25ObjectsResultInCountMessage(): void
    {
        $counter = 1;
        $withIds = [];
        do {
            $this->helper->storeSummaryNotification('Foo', 'Bar', $counter);
            $withIds[$counter] = $counter;
            ++$counter;
        } while ($counter <= 26);

        $this->ownerProvider->expects($this->once())
            ->method('getOwnersForObjectIds')
            ->with(Contact::NAME, $withIds)
            ->willReturn([]);

        $this->userHelper->expects($this->once())
            ->method('getAdminUsers')
            ->willReturn([1]);

        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->willReturnCallback(
                function ($string, $params) {
                    $expectedStrings = [
                        'mautic.integration.sync.user_notification.header',
                        'mautic.integration.sync.user_notification.count_message',
                    ];

                    if (!in_array($string, $expectedStrings)) {
                        $this->fail($string.' is not an expected translation key');
                    }

                    return $string;
                }
            );

        $this->writer->expects($this->exactly(1))
            ->method('writeUserNotification');

        $this->routeHelper->expects($this->never())
            ->method('getLinkCsv');

        $this->helper->writeNotifications(Contact::NAME, 'test');
    }
}
