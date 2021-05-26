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

namespace OAT\SimpleRoster\Tests\Unit\Request\ParamConverter;

use OAT\SimpleRoster\Bulk\Operation\BulkOperationCollection;
use OAT\SimpleRoster\Request\ParamConverter\BulkOperationCollectionParamConverter;
use PHPUnit\Framework\TestCase;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;

class BulkOperationCollectionParamConverterTest extends TestCase
{
    private BulkOperationCollectionParamConverter $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new BulkOperationCollectionParamConverter();
    }

    public function testItIsAParamConverter(): void
    {
        self::assertInstanceOf(ParamConverterInterface::class, $this->subject);
    }

    public function testItSupportsBulkOperationCollection(): void
    {
        $paramConverter = new ParamConverter([]);
        $paramConverter->setClass(BulkOperationCollection::class);

        self::assertTrue($this->subject->supports($paramConverter));
    }

    public function testItSetsBulkOperationAsRequestAttribute(): void
    {
        $paramConverter = new ParamConverter([]);
        $paramConverter->setClass(BulkOperationCollection::class);

        $expectedParameterName = 'bulkCollection';

        $paramConverter->setName($expectedParameterName);

        /** @var string $requestBodyContent */
        $requestBodyContent = json_encode([
            ['identifier' => 'user1'],
            ['identifier' => 'user2'],
        ], JSON_THROW_ON_ERROR, 512);

        $request = Request::create(
            '/test',
            'POST',
            [],
            [],
            [],
            [],
            $requestBodyContent
        );

        $this->subject->apply($request, $paramConverter);

        self::assertTrue($request->attributes->has($expectedParameterName));

        /** @var BulkOperationCollection $bulkOperationCollection */
        $bulkOperationCollection = $request->attributes->get($expectedParameterName);

        self::assertInstanceOf(BulkOperationCollection::class, $bulkOperationCollection);

        self::assertCount(2, $bulkOperationCollection);
    }
}
