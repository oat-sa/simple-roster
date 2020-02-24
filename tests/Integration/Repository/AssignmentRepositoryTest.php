<?php

declare(strict_types=1);

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

namespace App\Tests\Integration\Repository;

use App\Entity\Assignment;
use App\Repository\AssignmentRepository;
use App\Tests\Traits\DatabaseTestingTrait;
use DateInterval;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AssignmentRepositoryTest extends KernelTestCase
{
    use DatabaseTestingTrait;

    /** @var AssignmentRepository */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
        $this->loadFixtureByFilename('usersWithStartedButStuckAssignments.yml');

        $this->subject = self::$container->get(AssignmentRepository::class);
    }

    public function testItCanReturnAssignmentsByStateAndUpdatedAt(): void
    {
        $dateTime = (new DateTime())->add(new DateInterval('P1D'));
        $assignments = $this->subject->findAllByStateAndUpdatedAtPaginated(Assignment::STATE_STARTED, $dateTime);

        $this->assertCount(10, $assignments->getIterator());
        $this->assertCount(10, $assignments);
    }

    public function testItCanReturnAssignmentsByStateAndUpdatedAtPaginated(): void
    {
        $dateTime = (new DateTime())->add(new DateInterval('P1D'));
        $assignments = $this->subject->findAllByStateAndUpdatedAtPaginated(Assignment::STATE_STARTED, $dateTime, 2, 3);

        $this->assertCount(3, $assignments->getIterator());
        $this->assertCount(10, $assignments);
    }
}
