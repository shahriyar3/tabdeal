<?php

namespace Mautic\EmailBundle\Tests\MonitoredEmail\Processor;

use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscribe;
use Mautic\EmailBundle\MonitoredEmail\Search\ContactFinder;
use Mautic\EmailBundle\MonitoredEmail\Search\Result;
use Mautic\EmailBundle\Tests\MonitoredEmail\Transport\TestTransport;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\DoNotContact;
use Monolog\Logger;
use Symfony\Component\Mailer\Transport\NullTransport;

class UnsubscribeTest extends \PHPUnit\Framework\TestCase
{
    #[\PHPUnit\Framework\Attributes\TestDox('Test that the transport interface processes the message appropriately')]
    public function testProcessorInterfaceProcessesMessage(): void
    {
        $transport     = new TestTransport();
        $contactFinder = $this->createMock(ContactFinder::class);
        $contactFinder->method('find')
            ->willReturnCallback(
                function ($email) {
                    $stat = new Stat();

                    $lead = new Lead();
                    $lead->setEmail($email);
                    $stat->setLead($lead);

                    $email = new Email();
                    $stat->setEmail($email);

                    $result = new Result();
                    $result->setStat($stat);
                    $result->setContacts(
                        [
                            $lead,
                        ]
                    );

                    return $result;
                }
            );

        $translator = $this->createMock(Translator::class);

        $logger = $this->createMock(Logger::class);

        $doNotContact = $this->createMock(DoNotContact::class);

        $processor = new Unsubscribe($transport, $contactFinder, $translator, $logger, $doNotContact);

        $message = new Message();
        $this->assertTrue($processor->process($message));
    }

    #[\PHPUnit\Framework\Attributes\TestDox('Test that the message is processed appropriately')]
    public function testContactIsFoundFromMessageAndDncRecordAdded(): void
    {
        $transport     = new NullTransport();
        $contactFinder = $this->createMock(ContactFinder::class);
        $contactFinder->method('find')
            ->willReturnCallback(
                function ($email) {
                    $stat = new Stat();

                    $lead = new Lead();
                    $lead->setEmail($email);
                    $stat->setLead($lead);

                    $email = new Email();
                    $stat->setEmail($email);

                    $result = new Result();
                    $result->setStat($stat);
                    $result->setContacts(
                        [
                            $lead,
                        ]
                    );

                    return $result;
                }
            );

        $translator = $this->createMock(Translator::class);

        $logger = $this->createMock(Logger::class);

        $doNotContact = $this->createMock(DoNotContact::class);

        $processor = new Unsubscribe($transport, $contactFinder, $translator, $logger, $doNotContact);

        $message     = new Message();
        $message->to = ['contact+unsubscribe_123abc@test.com' => null];
        $this->assertTrue($processor->process($message));
    }
}
