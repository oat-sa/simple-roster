<?php

/*
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
 *  Copyright (c) 2021 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Integration\Security\Generator;

use Carbon\Carbon;
use DateTimeImmutable;
use Lcobucci\JWT\Token\DataSet;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Security\Generator\JwtTokenGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid\UuidFactoryInterface;
use Ramsey\Uuid\UuidInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;

class JwtTokenGeneratorTest extends KernelTestCase
{
    /** @var JwtTokenGenerator */
    private $subject;

    /** @var UuidFactoryInterface|MockObject */
    private $testUuidFactory;

    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        $this->testUuidFactory = $this->createMock(UuidFactoryInterface::class);

        self::getContainer()->set('test.uid_generator', $this->testUuidFactory);

        $this->subject = self::getContainer()->get(JwtTokenGenerator::class);
    }

    public function testItCanCreateTokenWithClaims(): void
    {
        $expectedUuid = '123456';

        $uuid = $this->createMock(UuidInterface::class);
        $uuid
            ->expects(self::once())
            ->method('toString')
            ->willReturn($expectedUuid);

        $this->testUuidFactory
            ->expects(self::once())
            ->method('uuid4')
            ->willReturn($uuid);

        $testUser = (new User())->setUsername('testUsername');
        $request = Request::create(
            '/test',
            'GET',
            [],
            [],
            [],
            ['HTTP_HOST' => 'test.com']
        );

        Carbon::setTestNow(Carbon::create(2019));

        $token = $this->subject->create($testUser, $request, 'testSubject', 199);

        self::assertSame('http://test.com', $token->claims()->get('iss'));
        self::assertSame('testSubject', $token->claims()->get('sub'));
        self::assertSame('testUsername', $token->claims()->get('aud')[0]);
        self::assertSame($expectedUuid, $token->claims()->get('jti'));
        $this->assertDatetimeClaim($token->claims(), 'iat', '2019-01-01T00:00:00+00:00');
        $this->assertDatetimeClaim($token->claims(), 'nbf', '2019-01-01T00:00:00+00:00');
        $this->assertDatetimeClaim($token->claims(), 'exp', '2019-01-01T00:03:19+00:00');

        Carbon::setTestNow();
    }

    private function assertDatetimeClaim(DataSet $claims, string $claim, string $dateTimeAtom): void
    {
        $expectedDate = $claims->get($claim);
        if (!$expectedDate instanceof DateTimeImmutable) {
            self::fail(sprintf("Claim '%s' expected to be instace of %s", $claim, DateTimeImmutable::class));
        }

        self::assertSame($dateTimeAtom, $expectedDate->format(DATE_ATOM));
    }
}
