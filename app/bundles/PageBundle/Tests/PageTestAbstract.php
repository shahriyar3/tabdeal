<?php

namespace Mautic\PageBundle\Tests;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\CookieHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Shortener\Shortener;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\Helper\BotRatioHelper;
use Mautic\LeadBundle\Helper\ContactRequestHelper;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\LeadBundle\Tracker\DeviceTracker;
use Mautic\PageBundle\Entity\HitRepository;
use Mautic\PageBundle\Entity\PageRepository;
use Mautic\PageBundle\Model\PageModel;
use Mautic\PageBundle\Model\RedirectModel;
use Mautic\PageBundle\Model\TrackableModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PageTestAbstract extends TestCase
{
    protected static $mockId   = 123;

    protected static $mockName = 'Mock test name';

    protected string $mockTrackingId;

    /**
     * @var Router|MockObject
     */
    protected $router;

    protected function setUp(): void
    {
        $this->mockTrackingId = hash('sha1', uniqid(mt_rand(), true));
    }

    /**
     * @return PageModel
     */
    protected function getPageModel($transliterationEnabled = true)
    {
        $cookieHelper = $this->createMock(CookieHelper::class);

        $this->router = $this->createMock(Router::class);

        $ipLookupHelper = $this->createMock(IpLookupHelper::class);

        $leadModel = $this->createMock(LeadModel::class);

        $leadFieldModel = $this->createMock(FieldModel::class);

        $redirectModel = $this->getRedirectModel();

        $companyModel = $this->createMock(CompanyModel::class);

        $trackableModel = $this->createMock(TrackableModel::class);

        $dispatcher = $this->createMock(EventDispatcher::class);

        $translator = $this->createMock(Translator::class);

        $entityManager = $this->createMock(EntityManager::class);

        $pageRepository = $this->createMock(PageRepository::class);

        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);

        $hitRepository = $this->createMock(HitRepository::class);
        $userHelper    = $this->createMock(UserHelper::class);

        $messageBus = $this->createMock(MessageBus::class);

        $contactTracker = $this->createMock(ContactTracker::class);

        /** @var ContactRequestHelper&MockObject $contactRequestHelper */
        $contactRequestHelper = $this->createMock(ContactRequestHelper::class);

        $contactTracker->expects($this
            ->any())
            ->method('getContact')
            ->willReturn(['id' => self::$mockId, 'name' => self::$mockName]);

        $entityManager->expects($this
            ->any())
            ->method('getRepository')
            ->willReturnMap(
                [
                    [\Mautic\PageBundle\Entity\Page::class, $pageRepository],
                    [\Mautic\PageBundle\Entity\Hit::class, $hitRepository],
                ]
            );

        $coreParametersHelper->expects($this->any())
                ->method('get')
                ->with('transliterate_page_title')
                ->willReturn($transliterationEnabled);

        $deviceTrackerMock           = $this->createMock(DeviceTracker::class);
        $statRepositoryMock          = $this->createMock(StatRepository::class);
        $botRatioHelperMock          = $this->createMock(BotRatioHelper::class);

        $pageModel = new PageModel(
            $cookieHelper,
            $ipLookupHelper,
            $leadModel,
            $leadFieldModel,
            $redirectModel,
            $trackableModel,
            $messageBus,
            $companyModel,
            $deviceTrackerMock,
            $contactTracker,
            $coreParametersHelper,
            $contactRequestHelper,
            $entityManager,
            $this->createMock(CorePermissions::class),
            $dispatcher,
            $this->router,
            $translator,
            $userHelper,
            $this->createMock(LoggerInterface::class),
            $statRepositoryMock,
            $botRatioHelperMock
        );

        return $pageModel;
    }

    /**
     * @return RedirectModel
     */
    protected function getRedirectModel()
    {
        $shortener = $this->createMock(Shortener::class);

        $mockRedirectModel = $this->getMockBuilder(RedirectModel::class)
            ->setConstructorArgs([
                $this->createMock(EntityManagerInterface::class),
                $this->createMock(CorePermissions::class),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(UrlGeneratorInterface::class),
                $this->createMock(Translator::class),
                $this->createMock(UserHelper::class),
                $this->createMock(LoggerInterface::class),
                $this->createMock(CoreParametersHelper::class),
                $shortener,
            ])
            ->onlyMethods(['createRedirectEntity', 'generateRedirectUrl'])
            ->getMock();

        $mockRedirect = $this->createMock(\Mautic\PageBundle\Entity\Redirect::class);

        $mockRedirectModel->expects($this->any())
            ->method('createRedirectEntity')
            ->willReturn($mockRedirect);

        $mockRedirectModel->expects($this->any())
            ->method('generateRedirectUrl')
            ->willReturn('http://some-url.com');

        return $mockRedirectModel;
    }
}
