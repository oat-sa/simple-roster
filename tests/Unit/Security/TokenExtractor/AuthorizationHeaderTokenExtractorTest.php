<?php declare(strict_types=1);
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

namespace App\Tests\Unit\Security\TokenExtractor;

use App\Security\TokenExtractor\AuthorizationHeaderTokenExtractor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class AuthorizationHeaderTokenExtractorTest extends TestCase
{
    /** @var AuthorizationHeaderTokenExtractor */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new AuthorizationHeaderTokenExtractor();
    }

    public function testExtractWithMissingAuthorizationHeader(): void
    {
        $request = new Request();

        $this->assertNull($this->subject->extract($request));
    }

    public function testExtractWithInvalidAuthorizationHeader(): void
    {
        $request = new Request([], [], [], [], [], ['HTTP_AUTHORIZATION' => 'invalid']);

        $this->assertNull($this->subject->extract($request));
    }

    public function testExtractWithValidAuthorizationHeader(): void
    {
        $request = new Request([], [], [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer 12345']);

        $this->assertEquals('12345', $this->subject->extract($request));
    }
}
