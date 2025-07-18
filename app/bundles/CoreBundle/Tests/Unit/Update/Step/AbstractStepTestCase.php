<?php

namespace Mautic\CoreBundle\Tests\Unit\Update\Step;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractStepTestCase extends TestCase
{
    /**
     * @var ProgressBar
     */
    protected $progressBar;

    /**
     * @var MockObject|InputInterface
     */
    protected $input;

    /**
     * @var MockObject|OutputInterface
     */
    protected $output;

    protected function setUp(): void
    {
        $formatter = $this->createMock(OutputFormatterInterface::class);
        $formatter->method('isDecorated')
          ->willReturn(false);

        $this->input      = $this->createMock(InputInterface::class);
        $this->output     = $this->createMock(OutputInterface::class);

        $this->output->method('getFormatter')
            ->willReturn($formatter);

        $this->progressBar = new ProgressBar($this->output);
    }
}
