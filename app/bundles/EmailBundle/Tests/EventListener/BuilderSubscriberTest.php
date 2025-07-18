<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\EventListener\BuilderSubscriber;
use Mautic\EmailBundle\Helper\MailHashHelper;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Model\RedirectModel;
use Mautic\PageBundle\Model\TrackableModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class BuilderSubscriberTest extends TestCase
{
    /**
     * @var MockObject|CoreParametersHelper
     */
    private $coreParametersHelper;
    /**
     * @var BuilderSubscriber
     */
    private $builderSubscriber;
    /**
     * @var MockObject|EmailModel
     */
    private $emailModel;
    /**
     * @var MockObject|TrackableModel
     */
    private $trackableModel;
    /**
     * @var MockObject|RedirectModel
     */
    private $redirectModel;

    /**
     * @var MockObject|TranslatorInterface
     */
    private $translator;

    public function __construct(?string $name = null)
    {
        parent::__construct($name);

        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->emailModel           = $this->createMock(EmailModel::class);
        $this->trackableModel       = $this->createMock(TrackableModel::class);
        $this->redirectModel        = $this->createMock(RedirectModel::class);
        $this->translator           = $this->createMock(TranslatorInterface::class);
        $mailHashHelper             = new MailHashHelper($this->coreParametersHelper);
        $this->builderSubscriber    = new BuilderSubscriber(
            $this->coreParametersHelper,
            $this->emailModel,
            $this->trackableModel,
            $this->redirectModel,
            $this->translator,
            $mailHashHelper
        );
        $this->emailModel->method('buildUrl')->willReturn('https://some.url');
        $this->translator->method('trans')->willReturn('some translation');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('fixEmailAccessibilityContent')]
    public function testFixEmailAccessibility(string $content, string $expectedContent, ?string $emailLocale): void
    {
        $this->coreParametersHelper->method('get')->willReturnCallback(function ($key) {
            if ('locale' === $key) {
                return 'default_locale';
            }

            return false;
        });

        $email = new Email();
        $email->setSubject('A unicorn spotted in Alaska');
        $email->setLanguage($emailLocale);

        $emailSendEvent = new EmailSendEvent(null, ['email' => $email]);
        $emailSendEvent->setContent($content);
        $this->builderSubscriber->fixEmailAccessibility($emailSendEvent);
        $this->builderSubscriber->onEmailGenerate($emailSendEvent);
        $this->assertSame($expectedContent, $emailSendEvent->getContent());
    }

    /**
     * @return iterable<array<int,string>>
     */
    public static function fixEmailAccessibilityContent(): iterable
    {
        yield [
            '<html><head></head></html>',
            '<html lang="en"><head><title>A unicorn spotted in Alaska</title></head></html>',
            'en',
        ];
        yield [
            '<html><head></head></html>',
            '<html lang="es"><head><title>A unicorn spotted in Alaska</title></head></html>',
            'es',
        ];
        yield [
            '<html><head></head></html>',
            '<html lang="default_locale"><head><title>A unicorn spotted in Alaska</title></head></html>',
            '',
        ];
        yield [
            "<html>\n\n<head>\n</head>\n</html>",
            "<html lang=\"en\">\n\n<head>\n<title>A unicorn spotted in Alaska</title></head>\n</html>",
            'en',
        ];
        yield [
            '<html lang="en"><head></head></html>',
            '<html lang="en"><head><title>A unicorn spotted in Alaska</title></head></html>',
            'en',
        ];
        yield [
            '<html lang="en"><head></head></html>',
            '<html lang="en"><head><title>A unicorn spotted in Alaska</title></head></html>',
            'es',
        ];
        yield [
            '<html lang="cs_CZ"><head></head></html>',
            '<html lang="cs_CZ"><head><title>A unicorn spotted in Alaska</title></head></html>',
            'en',
        ];
        yield [
            '<html lang="en"><head><title>Existed Title</title></head></html>',
            '<html lang="en"><head><title>Existed Title</title></head></html>',
            'en',
        ];
        yield [
            '<head><title>Existed Title</title></head>',
            '<head><title>Existed Title</title></head>',
            'en',
        ];
        yield [
            '<html><body>xxx</body></html>',
            '<html lang="en"><head><title>A unicorn spotted in Alaska</title></head><body>xxx</body></html>',
            'en',
        ];
    }

    public function testUnsubscribeTestTokensAreReplacedOnEmailGenerate(): void
    {
        $lead = new Lead();
        $lead->setId(7);
        $lead->setLastname('Boss');

        $company = new Company();
        $company->setName('ACME');

        $leadArray                = $lead->convertToArray();
        $leadArray['companies'][] = ['companyname' => $company->getName(), 'is_primary' => true];

        $args = [
            'lead' => $leadArray,
            'email'=> (new Email()),
        ];
        $event = new EmailSendEvent(null, $args);

        $unsubscribeTokenizedText = '{contactfield=companyname} {contactfield=lastname}';
        $matcher                  = $this->exactly(5);

        $this->coreParametersHelper->expects($matcher)
            ->method('get')->willReturnCallback(function (...$parameters) use ($matcher, $unsubscribeTokenizedText) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('unsubscribe_text', $parameters[0]);

                    return $unsubscribeTokenizedText;
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame('webview_text', $parameters[0]);

                    return 'Just a text';
                }
                if (3 === $matcher->numberOfInvocations()) {
                    $this->assertSame('default_signature_text', $parameters[0]);

                    return 'Signature';
                }
                if (4 === $matcher->numberOfInvocations()) {
                    $this->assertSame('mailer_from_name', $parameters[0]);

                    return 'jan.kozak@acquia.com';
                }
                if (5 === $matcher->numberOfInvocations()) {
                    $this->assertSame('brand_name', $parameters[0]);

                    return 'ACME';
                }
            });

        $this->translator->expects($this->never())
            ->method('trans');

        $this->builderSubscriber->onEmailGenerate($event);
        $this->assertEquals(
            $company->getName().' '.$lead->getLastname(),
            $event->getTokens()['{unsubscribe_text}']
        );
    }
}
