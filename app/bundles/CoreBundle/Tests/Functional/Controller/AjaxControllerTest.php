<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Controller;

use Mautic\ApiBundle\Entity\oAuth2\Client;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\ChannelBundle\Entity\Channel;
use Mautic\ChannelBundle\Entity\Message;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\EmailBundle\Entity\Email;
use Mautic\FormBundle\Entity\Form;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\NotificationBundle\Entity\Notification;
use Mautic\PageBundle\Entity\Page;
use Mautic\PointBundle\Entity\Group;
use Mautic\PointBundle\Entity\Point;
use Mautic\PointBundle\Entity\Trigger;
use Mautic\ReportBundle\Entity\Report;
use Mautic\SmsBundle\Entity\Sms;
use Mautic\StageBundle\Entity\Stage;
use Mautic\UserBundle\Entity\Permission;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use MauticPlugin\MauticFocusBundle\Entity\Focus;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

final class AjaxControllerTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataForGlobalSearch')]
    public function testGlobalSearch(string $searchString, mixed $entity, string $expectedLink): void
    {
        $this->em->persist($entity);

        $this->em->flush();
        $this->em->clear();

        $this->client->xmlHttpRequest(Request::METHOD_GET, '/s/ajax?action=globalSearch&global_search='.$searchString.'&tmp=list');
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());

        $content      = \json_decode($response->getContent(), true);
        $expectedLink = rtrim($expectedLink, '/').'/'.$entity->getId();

        $this->assertStringContainsString($expectedLink, $content['newContent']);
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function dataForGlobalSearch(): iterable
    {
        // Api
        $apiClient = self::createApiClient('client');

        yield 'Search API' => [
            'client',
            $apiClient,
            's/credentials/edit/',
        ];

        // Asset
        $asset = self::createAsset();

        yield 'Search Asset' => [
            'asset',
            $asset,
            's/assets/view/',
        ];

        // Campaign
        $campaign = self::createCampaign();

        yield 'Search Campaign' => [
            'campaign',
            $campaign,
            's/campaigns/view/',
        ];

        // Channel: Marketing Messages
        $message = self::createMessage();

        yield 'Search Marketing Messages' => [
            'marketing',
            $message,
            's/messages/view/',
        ];

        // Dynamic Content
        $dwc = self::createDynamicContent();

        yield 'Search Dynamic Content' => [
            'dynamic',
            $dwc,
            's/dwc/view/',
        ];

        // Email
        $email = self::createEmail();

        yield 'Search Email' => [
            'email',
            $email,
            's/emails/view/',
        ];

        // Form
        $form = self::createForm();

        yield 'Search Forms' => [
            'form',
            $form,
            's/forms/view/',
        ];

        // Lead
        $lead = self::createLead();

        yield 'Search Contact' => [
            'john',
            $lead,
            's/contacts/view/',
        ];

        // Companies
        $company = self::createCompany();

        yield 'Search Companies' => [
            'maut',
            $company,
            's/companies/view/',
        ];

        // Segment
        $segment = self::createSegment();

        yield 'Search Segment' => [
            'news',
            $segment,
            's/segments/view/',
        ];

        // Notification Mobile
        $notification = self::createNotification('Notification Mobile', true);

        yield 'Search Notification Mobile' => [
            'mobile',
            $notification,
            's/mobile_notifications/edit/',
        ];

        // Notification Web
        $notification = self::createNotification('Notification Web');

        yield 'Search Notification Web' => [
            'web',
            $notification,
            's/notifications/view/',
        ];

        // Page
        $page = self::createPage();

        yield 'Search Page' => [
            'landing',
            $page,
            's/pages/view/',
        ];

        // Point Action
        $pointAction = self::createPointAction();

        yield 'Search Point Action' => [
            'action',
            $pointAction,
            's/points/edit/',
        ];

        // Point Group
        $pointGroup = self::createPointGroup();

        yield 'Search Points Group' => [
            'group',
            $pointGroup,
            's/points/groups/edit/',
        ];

        // Point Trigger
        $pointTrigger = self::createPointTrigger();

        yield 'Search Point Trigger' => [
            'trigger',
            $pointTrigger,
            's/triggers/edit/',
        ];

        // Report
        $report = self::createReport();

        yield 'Search Report' => [
            'lead',
            $report,
            's/reports/view/',
        ];

        // SMS
        $sms = new Sms();
        $sms->setName('Welcome text');
        $sms->setMessage('Hello there!');

        yield 'Search SMS' => [
            'welcome',
            $sms,
            's/sms/view/',
        ];

        // Stage
        $stage = self::createStage();

        yield 'Search Stage' => [
            'stage',
            $stage,
            's/stages/edit/',
        ];

        // Role
        $role = new Role();
        $role->setName('Editor');

        yield 'Search Role' => [
            'edit',
            $role,
            's/roles/edit/',
        ];

        // Focus
        $focus = self::createFocus('Focus');

        yield 'Search Focus' => [
            'focus',
            $focus,
            's/focus/view/',
        ];

        // Tag
        $tag = new Tag();
        $tag->setTag('Tag');

        yield 'Search tag' => [
            'tag',
            $tag,
            's/tags/view/',
        ];
    }

    public function testGlobalSearchForUser(): void
    {
        $user = $this->createUser([
            'user-name'     => 'user',
            'email'         => 'user@mautic-test.com',
            'first-name'    => 'user',
            'last-name'     => 'user',
            'role'          => [
                'name'      => 'perm',
                'perm'      => 'api:clients',
                'bitwise'   => 32, // just create
            ],
        ]);

        $this->em->clear();

        $this->client->xmlHttpRequest(Request::METHOD_GET, '/s/ajax?action=globalSearch&global_search=user&tmp=list');
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());

        $content = \json_decode($response->getContent(), true);
        $this->assertStringContainsString('s/users/edit/'.$user->getId(), $content['newContent']);
    }

    /**
     * @param array<string, string|int> $roleData
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('dataForGlobalSearchForNonAdminUser')]
    public function testGlobalSearchForNonAdminUser(
        string $searchString,
        mixed $entity,
        array $roleData,
        string $notExpectedLink,
    ): void {
        $this->em->persist($entity);

        $this->em->flush();
        $this->em->clear();

        $user = $this->createUser([
            'user-name'     => 'user-view-own',
            'email'         => 'user-view-own@mautic-test.com',
            'first-name'    => 'user-view-own',
            'last-name'     => 'user-view-own',
            'role'          => $roleData,
        ]);

        $this->loginOtherUser($user->getUserIdentifier());

        $this->client->xmlHttpRequest(Request::METHOD_GET, '/s/ajax?action=globalSearch&global_search='.$searchString.'&tmp=list');

        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk(), $response->getContent());
        $content = \json_decode($response->getContent(), true);
        $this->assertArrayHasKey('newContent', $content);

        $this->assertGlobalSearchNotResult($content['newContent']);

        $this->assertStringNotContainsString($notExpectedLink, $content['newContent']);
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function dataForGlobalSearchForNonAdminUser(): iterable
    {
        $apiClient = self::createApiClient('Client');

        yield 'Search API' => [
            // Search string
            'client',
            // Object
            $apiClient,
            // role
            [
                'name'      => 'perm_user_view',
                'perm'      => 'api:clients',
                'bitwise'   => 32, // just create
            ],
            // link
            's/credentials/edit/',
        ];

        $asset = self::createAsset();
        yield 'Search Asset' => [
            // Search string
            'asset',
            // Object
            $asset,
            // role
            [
                'name'      => 'perm_edit_own',
                'perm'      => 'asset:assets',
                'bitwise'   => 42, // just viewown, editown, create
            ],
            // link
            's/assets/view/',
        ];

        $form = self::createForm();
        yield 'Search Form' => [
            // Search string
            'form',
            // Object
            $form,
            // role
            [
                'name'      => 'perm_form_own',
                'perm'      => 'form:forms',
                'bitwise'   => 34, // just viewown, create
            ],
            // link
            's/forms/view/',
        ];
    }

    public function testGlobalSearchForRandomAndEmptyString(): void
    {
        $apiClient = self::createApiClient('client');
        $this->em->persist($apiClient);
        $this->em->flush();
        $this->em->clear();

        $searchString = '';
        $this->client->xmlHttpRequest(Request::METHOD_GET, '/s/ajax?action=globalSearch&global_search='.$searchString.'&tmp=list');
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());

        $content = \json_decode($response->getContent(), true);
        $this->assertGlobalSearchNotResult($content['newContent']);

        $searchString = 'random';
        $this->client->xmlHttpRequest(Request::METHOD_GET, '/s/ajax?action=globalSearch&global_search='.$searchString.'&tmp=list');
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());

        $content = \json_decode($response->getContent(), true);
        $this->assertGlobalSearchNotResult($content['newContent']);
    }

    public function testGlobalSearchForMoreLink(): void
    {
        $contactOne   = self::createLead('contact-1@mautic-test.com');
        $contactTwo   = self::createLead('contact-2@mautic-test.com');
        $contactThree = self::createLead('contact-3@mautic-test.com');
        $contactFour  = self::createLead('contact-4@mautic-test.com');

        $client1 = self::createApiClient('Client1');
        $client2 = self::createApiClient('Client2');
        $client3 = self::createApiClient('Client3');
        $client4 = self::createApiClient('Client4');

        $this->em->persist($contactOne);
        $this->em->persist($contactTwo);
        $this->em->persist($contactThree);
        $this->em->persist($contactFour);

        $this->em->persist($client1);
        $this->em->persist($client2);
        $this->em->persist($client3);
        $this->em->persist($client4);

        $this->em->flush();
        $this->em->clear();

        $searchString = 'john';
        $this->client->xmlHttpRequest(Request::METHOD_GET, '/s/ajax?action=globalSearch&global_search='.$searchString.'&tmp=list');
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());

        $content = \json_decode($response->getContent(), true);

        $translator = self::getContainer()->get('translator');
        $this->assertStringContainsString('s/contacts?search='.$searchString, $content['newContent']);
        $this->assertStringContainsString($translator->trans('mautic.core.search.more', ['%count%' => 1]), $content['newContent']);

        $crawler = new Crawler($content['newContent']);
        $this->assertCount(4, $crawler->filterXPath("//li[contains(@class, 'gsearch--results-item')]"));

        $searchString = 'client';
        $this->client->xmlHttpRequest(Request::METHOD_GET, '/s/ajax?action=globalSearch&global_search='.$searchString.'&tmp=list');
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());

        $content = \json_decode($response->getContent(), true);

        $translator = self::getContainer()->get('translator');
        $this->assertStringContainsString('s/credentials?search='.$searchString, $content['newContent']);
        $this->assertStringContainsString($translator->trans('mautic.core.search.more', ['%count%' => 1]), $content['newContent']);

        $crawler = new Crawler($content['newContent']);
        $this->assertCount(4, $crawler->filterXPath("//li[contains(@class, 'gsearch--results-item')]"));
    }

    private function loginOtherUser(string $name): void
    {
        $this->client->request(Request::METHOD_GET, '/s/logout');
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => $name]);

        $this->loginUser($user);
        $this->client->setServerParameter('PHP_AUTH_USER', $name);
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');
    }

    /**
     * @param array<string, mixed> $userDetails
     */
    private function createUser(array $userDetails): User
    {
        $role = new Role();
        $role->setName($userDetails['role']['name']);
        $role->setIsAdmin(false);

        $this->em->persist($role);

        $this->createPermission($role, $userDetails['role']['perm'], $userDetails['role']['bitwise']);

        $user = new User();
        $user->setEmail($userDetails['email']);
        $user->setUsername($userDetails['user-name']);
        $user->setFirstName($userDetails['first-name']);
        $user->setLastName($userDetails['last-name']);
        $user->setRole($role);

        $hasher = self::getContainer()->get('security.password_hasher_factory')->getPasswordHasher($user);
        \assert($hasher instanceof PasswordHasherInterface);
        $user->setPassword($hasher->hash('Maut1cR0cks!'));

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function createPermission(Role $role, string $rawPermission, int $bitwise): void
    {
        $parts      = explode(':', $rawPermission);
        $permission = new Permission();
        $permission->setBundle($parts[0]);
        $permission->setName($parts[1]);
        $permission->setRole($role);
        $permission->setBitwise($bitwise);
        $this->em->persist($permission);
    }

    private static function createApiClient(string $name): Client
    {
        $apiClient = new Client();
        $apiClient->setName($name);
        $apiClient->setRedirectUris(['https://example.com/'.$name]);

        return $apiClient;
    }

    private static function createAsset(): Asset
    {
        $asset = new Asset();
        $asset->setTitle('Asset');
        $asset->setAlias('asset');

        return $asset;
    }

    private static function createCampaign(): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('Campaign');

        return $campaign;
    }

    private static function createMessage(): Message
    {
        $channel = new Channel();
        $channel->setChannel('email');
        $channel->setChannelId(12);
        $channel->setIsEnabled(true);

        $message = new Message();
        $message->setName('Marketing Message');
        $message->setDescription('random text string for description');
        $message->addChannel($channel);

        return $message;
    }

    private static function createDynamicContent(): DynamicContent
    {
        $dwc = new DynamicContent();
        $dwc->setName('Dynamic Content');

        return $dwc;
    }

    private static function createEmail(): Email
    {
        $email = new Email();
        $email->setName('Email');
        $email->setSubject('Subject');
        $email->setIsPublished(true);

        return $email;
    }

    private static function createForm(): Form
    {
        $form = new Form();
        $form->setName('Forms');
        $form->setAlias('form');

        return $form;
    }

    private static function createLead(string $email = 'jon@example.com'): Lead
    {
        $lead = new Lead();
        $lead->setFirstname('John');
        $lead->setEmail($email);

        return $lead;
    }

    private static function createCompany(): Company
    {
        $company = new Company();
        $company->setName('Mautic');

        return $company;
    }

    private static function createSegment(): LeadList
    {
        $segment = new LeadList();
        $segment->setAlias('newsletter');
        $segment->setName('Newsletter');
        $segment->setPublicName('Newsletter');
        $segment->setFilters([]);

        return $segment;
    }

    private static function createNotification(string $name, bool $isMobile = false): Notification
    {
        $notification = new Notification();
        $notification->setName($name);
        $notification->setHeading('Heading 1');
        $notification->setMessage('Message 1');
        $notification->setMobile($isMobile);

        return $notification;
    }

    private static function createPage(): Page
    {
        $page = new Page();
        $page->setTitle('Landing Page');
        $page->setAlias('landing-page');

        return $page;
    }

    private static function createPointAction(): Point
    {
        $pointAction = new Point();
        $pointAction->setName('Read email action');
        $pointAction->setDelta(1);
        $pointAction->setType('email.open');
        $pointAction->setProperties(['emails' => [1]]);

        return $pointAction;
    }

    private static function createPointGroup(): Group
    {
        $pointGroup = new Group();
        $pointGroup->setName('New Group');

        return $pointGroup;
    }

    private static function createPointTrigger(): Trigger
    {
        $pointTrigger = new Trigger();
        $pointTrigger->setName('Trigger');

        return $pointTrigger;
    }

    private static function createReport(): Report
    {
        $report = new Report();
        $report->setName('Lead and points');
        $report->setSource('lead.pointlog');

        return $report;
    }

    private static function createStage(): Stage
    {
        $stage = new Stage();
        $stage->setName('Stage');

        return $stage;
    }

    private static function createFocus(string $name): Focus
    {
        $focus = new Focus();
        $focus->setName($name);
        $focus->setType('link');
        $focus->setStyle('modal');
        $focus->setProperties([
            'bar' => [
                'allow_hide' => 1,
                'push_page'  => 1,
                'sticky'     => 1,
                'size'       => 'large',
                'placement'  => 'top',
            ],
            'modal' => [
                'placement' => 'top',
            ],
            'notification' => [
                'placement' => 'top_left',
            ],
            'page'            => [],
            'animate'         => 0,
            'link_activation' => 1,
            'colors'          => [
                'primary'     => '4e5d9d',
                'text'        => '000000',
                'button'      => 'fdb933',
                'button_text' => 'ffffff',
            ],
            'content' => [
                'headline'        => null,
                'tagline'         => null,
                'link_text'       => null,
                'link_url'        => null,
                'link_new_window' => 1,
                'font'            => 'Arial, Helvetica, sans-serif',
                'css'             => null,
            ],
            'when'                  => 'immediately',
            'timeout'               => null,
            'frequency'             => 'everypage',
            'stop_after_conversion' => 1,
        ]);

        return $focus;
    }

    private function assertGlobalSearchNotResult(string $newContent): void
    {
        $this->assertMatchesRegularExpression(
            '/<div.*?>\s*'.
            '<div.*?>\n'.
            '\s*<span class="fs-16">Navigate<\/span>\n'.
            '\s*<kbd><i class="ri-arrow-up-line"><\/i><\/kbd>\n'.
            '\s*<kbd><i class="ri-arrow-down-line"><\/i><\/kbd>\n'.
            '\s*<span class="fs-16">or<\/span>\n'.
            '\s*<kbd>tab<\/kbd>\n'.
            '\s*<\/div>\n'.
            '\s*<div.*?>\n'.
            '\s*<span class="fs-16">Close<\/span>\n'.
            '\s*<kbd>esc<\/kbd>\n'.
            '\s*<\/div>\n'.
            '\s*<\/div>\n\n'.
            '<div id="globalSearchPanel">\n'. // Matches the search panel div
            '\s*<!-- No results message -->\n'. // Matches the "No results message" comment
            '\s*<div class="text-center text-secondary mt-sm">\n'. // Matches the no-results div
            '\s*.*?\n'. // Matches random text inside the no-results div
            '\s*<\/div>\n'.
            '\s*<\/div>/s',
            $newContent
        );
    }
}
