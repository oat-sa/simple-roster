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
 *  Copyright (c) 2020 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Lti\Extractor;

use LogicException;
use OAT\SimpleRoster\Lti\Extractor\LoginHintExtractor;
use PHPUnit\Framework\TestCase;

class LoginHintExtractorTest extends TestCase
{
    /** @var LoginHintExtractor */
    private $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->subject = new LoginHintExtractor();
    }

    public function testShouldExtractDataWhenLoginHintIsWellFormed(): void
    {
        $loginHint = 'user::1';

        $loginHintDto = $this->subject->extract($loginHint);

        self::assertSame('user', $loginHintDto->getUsername());
        self::assertSame(1, $loginHintDto->getAssignmentId());
    }

    /**
     * @dataProvider provideInvalidLoginHints
     */
    public function testShouldThrowLogicExceptionIfLoginHintIsMalformed(string $loginHint, string $message): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage($message);

        $this->subject->extract($loginHint);
    }

    public function provideInvalidLoginHints(): array
    {
        return [
            'withoutSeparators' => [
                'loginHint' => 'user11',
                'message' => 'Invalid Login hint format.',
            ],
            'withWrongSeparators' => [
                'loginHint' => 'user1_1',
                'message' => 'Invalid Login hint format.',
            ],
            'withoutUsername' => [
                'loginHint' => '::1',
                'message' => 'Missing username on login hint.',
            ],
            'withoutAssignmentId' => [
                'loginHint' => 'username::groupId::',
                'message' => 'Missing assignment ID on login hint.',
            ],
        ];
    }
}
