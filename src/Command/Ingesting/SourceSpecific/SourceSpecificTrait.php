<?php declare(strict_types=1);

namespace App\Command\Ingesting\SourceSpecific;

use App\Ingesting\Source\SourceInterface;
use Symfony\Component\Console\Command\Command;

trait SourceSpecificTrait
{
    /**
     * @param $name
     * @param null $shortcut
     * @param null $mode
     * @param string $description
     * @param null $default
     * @return Command
     */
    abstract public function addOption($name, $shortcut = null, $mode = null, $description = '', $default = null);

    abstract protected function addSourceOptions();

    abstract protected function getSource(array $inputOptions): SourceInterface;
}