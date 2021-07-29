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
    private LoginHintExtractor $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->subject = new LoginHintExtractor();
    }

    /**
     * @dataProvider provideValidLoginHints
     */
    public function testItCanExtractLoginHint(
        string $loginHint,
        string $expectedUsername,
        int $expectedAssignmentId
    ): void {
        $loginHintDto = $this->subject->extract($loginHint);

        self::assertSame($expectedUsername, $loginHintDto->getUsername());
        self::assertSame($expectedAssignmentId, $loginHintDto->getAssignmentId());
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

    public function provideValidLoginHints(): array
    {
        return [
            'withAlphanumericUsername' => [
                'loginHint' => 'user2134::12',
                'expectedUsername' => 'user2134',
                'expectedAssignmentId' => 12,
            ],
            'withUsernameWithSpecialCharacters' => [
                'loginHint' => 'FIRSTNAME.LASTNAME-23_1::12',
                'expectedUsername' => 'FIRSTNAME.LASTNAME-23_1',
                'expectedAssignmentId' => 12,
            ],
        ];
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
                'message' => 'Invalid Login hint format.',
            ],
            'withoutAssignmentId' => [
                'loginHint' => 'username::',
                'message' => 'Invalid Login hint format.',
            ],
            'withInvalidStartingCharacterInUsername' => [
                'loginHint' => '!username::1',
                'message' => 'Invalid Login hint format.',
            ],
            'withInvalidEndingCharacterInAssignmentId' => [
                'loginHint' => 'username::1a',
                'message' => 'Invalid Login hint format.',
            ],
        ];
    }
}
