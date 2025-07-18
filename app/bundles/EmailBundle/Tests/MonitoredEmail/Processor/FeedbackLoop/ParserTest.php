<?php

namespace Mautic\EmailBundle\Tests\MonitoredEmail\Processor\FeedbackLoop;

use Mautic\EmailBundle\MonitoredEmail\Exception\FeedbackLoopNotFound;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\FeedbackLoop\Parser;

#[\PHPUnit\Framework\Attributes\CoversClass(Parser::class)]
class ParserTest extends \PHPUnit\Framework\TestCase
{
    #[\PHPUnit\Framework\Attributes\TestDox('Test that an email is found inside a feedback report')]
    public function testEmailIsFoundInFeedbackLoopEmail(): void
    {
        $message            = new Message();
        $message->fblReport = <<<'BODY'
Feedback-Type: abuse
User-Agent: SomeGenerator/1.0
Version: 1
Original-Mail-From: <somespammer@example.net>
Original-Rcpt-To: <user@example.com>
Received-Date: Thu, 8 Mar 2005 14:00:00 EDT
Source-IP: 192.0.2.2
Authentication-Results: mail.example.com
               smtp.mail=somespammer@example.com;
               spf=fail
Reported-Domain: example.net
Reported-Uri: http://example.net/earn_money.html
Reported-Uri: mailto:user@example.com
Removal-Recipient: user@example.com
BODY;

        $parser = new Parser($message);

        $email = $parser->parse();
        $this->assertEquals('user@example.com', $email);
    }

    #[\PHPUnit\Framework\Attributes\TestDox('Test that an exception is thrown if no feedback report is found')]
    public function testExceptionIsThrownWithFblNotFound(): void
    {
        $this->expectException(FeedbackLoopNotFound::class);

        $message = new Message();
        $parser  = new Parser($message);

        $parser->parse();
    }
}
