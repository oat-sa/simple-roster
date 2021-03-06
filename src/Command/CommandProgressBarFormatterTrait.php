<?php

/**
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; under version 2
 *  of the License (non-upgradable).
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  Copyright (c) 2019 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Command;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

trait CommandProgressBarFormatterTrait
{
    /** @var string */
    protected string $format = '<info>Progress:</info> %current%/%max% [%bar%] %percent:3s%% | ' .
    ' <info>Time:</info> %elapsed:6s% / %estimated:-6s% | <info>Memory:</info> %memory:6s%';

    public function createFormattedProgressBar(OutputInterface $output): ProgressBar
    {
        $progressBar = new ProgressBar($output);

        $progressBar->setFormat($this->format);

        return $progressBar;
    }
}
