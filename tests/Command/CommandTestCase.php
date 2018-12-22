<?php

namespace App\Tests\Command;

use function foo\func;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class CommandTestCase extends TestCase
{
    public function getInputMock(?array $optionValues = []): InputInterface
    {
        $input = $this->getMockBuilder(InputInterface::class)->setMethods([])->getMock();

        $valueMap = [];
        foreach ($optionValues as $optionName => $optionValue) {
            $valueMap[] = [$optionName, $optionValue];
        }

        $input->expects($this->any())
            ->method('getOption')
            ->willReturnMap(
                $valueMap
            );

        return $input;
    }

    public function getOutputMock(): OutputInterface
    {
        $output = $this->getMockBuilder(OutputInterface::class)->setMethods([])->getMock();

        $output->expects($this->any())
            ->method('getFormatter')
            ->will($this->returnValue($this->getMockBuilder(OutputFormatterInterface::class)->setMethods([])->getMock()));

        return $output;
    }
}