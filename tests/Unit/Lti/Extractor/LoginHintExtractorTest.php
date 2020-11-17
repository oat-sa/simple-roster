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

    /**
     * @dataProvider provideValidLoginHints
     */
    public function testShouldExtractDataWhenLoginHintIsWellFormed(
        string $loginHint,
        string $user,
        string $groupdId,
        string $slug
    ): void {
        $loginHintDto = $this->subject->extract($loginHint);

        self::assertSame($user, $loginHintDto->getUsername());
        self::assertSame($groupdId, $loginHintDto->getGroupId());
        self::assertSame($slug, $loginHintDto->getSlug());
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
            'withCompleteInformation' => [
                'loginHint' => 'user::groupId::slug',
                'user' => 'user',
                'groupId' => 'groupId',
                'slug' => 'slug',

            ],
            'withoutGroup' => [
                'loginHint' => 'user::::slug',
                'user' => 'user',
                'groupId' => '',
                'slug' => 'slug',
            ],
        ];
    }

    public function provideInvalidLoginHints(): array
    {
        return [
            'withoutSeparators' => [
                'loginHint' => 'userGroupIdSlug',
                'message' => 'Invalid Login hint format.',
            ],
            'withWrongSeparators' => [
                'loginHint' => 'user_groupId_slug',
                'message' => 'Invalid Login hint format.',
            ],
            'withoutUsername' => [
                'loginHint' => '::groupId::slug',
                'message' => 'Missing username on login hint.',
            ],
            'withoutSlug' => [
                'loginHint' => 'username::groupId::',
                'message' => 'Missing slug on login hint.',
            ],
        ];
    }
}
