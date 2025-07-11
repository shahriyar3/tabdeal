<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Controller;

use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\Mapping\MappingException;
use GuzzleHttp\Utils;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\CoreBundle\Tests\Functional\UserEntityTrait;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CampaignControllerTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;
    use UserEntityTrait;

    private Lead $contactOne;
    private Lead $contactTwo;
    private Lead $contactThree;
    private Campaign $campaign;

    /**
     * @throws NotSupported
     * @throws ORMException
     * @throws MappingException
     */
    public function testContactsGridForValidPermissions(): void
    {
        $nonAdminUser = $this->setupCampaignData(38);

        $this->loginOtherUser($nonAdminUser);

        $this->client->request(Request::METHOD_GET, '/s/campaigns/view/'.$this->campaign->getId().'/contact/1');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString($this->contactOne->getName(), $content);
        $this->assertStringContainsString($this->contactTwo->getName(), $content);
        $this->assertStringContainsString($this->contactThree->getName(), $content);
    }

    /**
     * @throws OptimisticLockException
     * @throws MappingException
     * @throws ORMException
     * @throws NotSupported
     */
    public function testContactsGridWhenIncompleteValidPermissions(): void
    {
        $nonAdminUser = $this->setupCampaignData();

        $this->loginOtherUser($nonAdminUser);

        $this->client->request(Request::METHOD_GET, '/s/campaigns/view/'.$this->campaign->getId().'/contact/1');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('No Contacts Found', $content, $content);
    }

    /**
     * @throws ORMException
     * @throws MappingException
     * @throws OptimisticLockException
     * @throws NotSupported
     */
    private function setupCampaignData(int $bitwise = 2): User
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->em->getRepository(User::class);
        $adminUser      = $userRepository->findOneBy(['username' => 'admin']);

        // create users
        $nonAdminUser = $this->createUserWithPermission([
            'user-name'  => 'non-admin',
            'email'      => 'non-admin@mautic-test.com',
            'first-name' => 'non-admin',
            'last-name'  => 'non-admin',
            'role'       => [
                'name'        => 'perm_non_admin',
                'permissions' => [
                    'lead:leads'         => $bitwise,
                    'campaign:campaigns' => 2,
                ],
            ],
        ]);

        // create contacts
        $this->contactOne   = $this->createLead('John', '', '', $adminUser);
        $this->contactTwo   = $this->createLead('Alex', '', '', $adminUser);
        $this->contactThree = $this->createLead('Gemini', '', '', $nonAdminUser);

        // Create Segment
        $segment = $this->createSegment('seg1', []);

        // Add contacts to segment
        $this->createListLead($segment, $this->contactOne);
        $this->createListLead($segment, $this->contactTwo);
        $this->createListLead($segment, $this->contactThree);

        $this->campaign = $this->createCampaign('Campaign');
        $this->campaign->addList($segment);

        $this->createEvent('Add 10 points', $this->campaign,
            'lead.changepoints',
            'action',
            ['points' => 10]
        );

        $this->em->flush();
        $this->em->clear();

        $this->testSymfonyCommand('mautic:campaigns:update', ['--campaign-id' => $this->campaign->getId(), '-vv']);

        return $nonAdminUser;
    }

    public function testCountsProcessedCampaignsMethodCountsProcessedCampaignsCorrectly(): void
    {
        $campaign = new Campaign();
        $campaign->setName('Test Campaign');
        $this->em->persist($campaign);

        $lead = new Lead();
        $lead->setFirstname('Test Lead');
        $this->em->persist($lead);

        $campaignEvent1 = new Event();
        $campaignEvent1->setCampaign($campaign);
        $campaignEvent1->setName('Send Email 1');
        $campaignEvent1->setType('email.send');
        $campaignEvent1->setEventType('action');
        $campaignEvent1->setProperties([]);
        $this->em->persist($campaignEvent1);

        $campaignEvent2 = new Event();
        $campaignEvent2->setCampaign($campaign);
        $campaignEvent2->setName('Jump to send email 1');
        $campaignEvent2->setType('campaign.jump_to_event');
        $campaignEvent2->setEventType('action');
        $campaignEvent2->setProperties([]);
        $this->em->persist($campaignEvent2);

        $campaignLead = new CampaignLead();
        $campaignLead->setCampaign($campaign);
        $campaignLead->setLead($lead);
        $campaignLead->setDateAdded(new \DateTime());
        $this->em->persist($campaignLead);

        $leadEventLog1 = new LeadEventLog();
        $leadEventLog1->setLead($lead);
        $leadEventLog1->setEvent($campaignEvent1);
        $leadEventLog1->setIsScheduled(true);
        $leadEventLog1->setRotation(1);
        $this->em->persist($leadEventLog1);

        $leadEventLog2 = new LeadEventLog();
        $leadEventLog2->setLead($lead);
        $leadEventLog2->setEvent($campaignEvent2);
        $leadEventLog1->setRotation(1);
        $this->em->persist($leadEventLog2);

        $leadEventLog3 = new LeadEventLog();
        $leadEventLog3->setLead($lead);
        $leadEventLog3->setEvent($campaignEvent1);
        $leadEventLog1->setRotation(2);
        $this->em->persist($leadEventLog3);

        $this->em->flush();

        $eventsStatistics         = $this->getEventsStatistics($campaign);
        $expectedEventsStatistics = [
            0 => [
                'successPercent' => '100%',
                'completed'      => '1',
                'pending'        => '1',
            ],
            1 => [
                'successPercent' => '100%',
                'completed'      => '1',
                'pending'        => '0',
            ],
        ];

        Assert::assertSame($expectedEventsStatistics, $eventsStatistics, 'Events statistics doesn\'t match the actual events in the database.');
    }

    private function getCrawler(Campaign $campaign): Crawler
    {
        $now    = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $before = $now->modify('-1 month');
        $after  = $now->modify('+1 month');
        $url    = sprintf('s/campaigns/event/stats/%d/%s/%s', $campaign->getId(), $before->format('Y-m-d'), $after->format('Y-m-d'));
        $this->client->request('GET', $url);
        $response = $this->client->getResponse();
        $body     = Utils::jsonDecode($response->getContent(), true);
        $this->client->restart();

        return new Crawler($body['actions']);
    }

    /**
     * @return array<array<string, string>>
     */
    private function getEventsStatistics(Campaign $campaign): array
    {
        $crawler = $this->getCrawler($campaign);
        $events  = [];
        for ($eventIndex = 0;; ++$eventIndex) {
            $node = $crawler->filter('.campaign-event-list')->filter('span')->eq($eventIndex * 3);
            if (1 > $node->count()) {
                break;
            }
            $events[] = [
                'successPercent' => trim($crawler->filter('.campaign-event-list')->filter('span')->eq($eventIndex * 3)->html()),
                'completed'      => trim($crawler->filter('.campaign-event-list')->filter('span')->eq($eventIndex * 3 + 1)->html()),
                'pending'        => trim($crawler->filter('.campaign-event-list')->filter('span')->eq($eventIndex * 3 + 2)->html()),
            ];
        }

        return $events;
    }
}
