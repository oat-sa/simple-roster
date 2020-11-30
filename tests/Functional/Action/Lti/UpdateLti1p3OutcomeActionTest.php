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

namespace OAT\SimpleRoster\Tests\Functional\Action\Lti;

use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Repository\AssignmentRepository;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactoryInterface;
use Ramsey\Uuid\UuidInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UpdateLti1p3OutcomeActionTest extends WebTestCase
{
    use DatabaseTestingTrait;

    /** @var KernelBrowser */
    private $kernelBrowser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernelBrowser = self::createClient();
        $this->setUpDatabase();
        $this->loadFixtureByFilename('userWithReadyAssignment.yml');
    }

    public function testItReturns401IfNotAuthenticated(): void
    {
        $this->kernelBrowser->request('POST', '/api/v1/lti/outcome');

        self::assertEquals(Response::HTTP_UNAUTHORIZED, $this->kernelBrowser->getResponse()->getStatusCode());
    }

    public function testItReturns200IfTheAuthenticationWorksAndAssignmentExists(): void
    {
        $this->setUuid4Value('e36f227c-2946-11e8-b467-0ed5f89f718b');

        /** @var string $xmlRequestBody */
        $xmlRequestBody = file_get_contents(
            __DIR__ . '/../../../Resources/LtiOutcome/valid_replace_result_body.xml'
        );

        /** @var string $xmlResponseBody */
        $xmlResponseBody = file_get_contents(
            __DIR__ . '/../../../Resources/LtiOutcome/valid_replace_result_response_body.xml'
        );

        $this->kernelBrowser->request(
            'POST',
            '/api/v1/lti1p3/outcome',
            [],
            [],
            [
                'CONTENT_TYPE' => 'text/xml',
                'Authorization' => $this->getValidToken()
            ],
            $xmlRequestBody
        );

        self::assertEquals(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());
        self::assertSame($xmlResponseBody, $this->kernelBrowser->getResponse()->getContent());

        self::assertEquals(
            Assignment::STATE_READY,
            $this->getAssignment()->getState()
        );
    }

    private function getAssignment(): Assignment
    {
        /** @var AssignmentRepository $repository */
        $repository = $this->getRepository(Assignment::class);

        $assignment = $repository->find(1);

        self::assertInstanceOf(Assignment::class, $assignment);

        return $assignment;
    }

    private function getValidToken(): string
    {
        return 'Bearer token';
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function setUuid4Value(string $uuid): void
    {
        $uuidInterface = $this->createMock(UuidInterface::class);

        $factory = $this->createMock(UuidFactoryInterface::class);
        $factory
            ->expects(self::once())
            ->method('uuid4')
            ->willReturn($uuidInterface);

        $uuidInterface
            ->expects(self::once())
            ->method('toString')
            ->willReturn($uuid);

        Uuid::setFactory($factory);
    }
}
