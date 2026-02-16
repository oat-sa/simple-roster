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

use OAT\SimpleRoster\Bulk\Operation\BulkOperation;
use OAT\SimpleRoster\Bulk\Operation\BulkOperationCollection;
use OAT\SimpleRoster\Http\Exception\RequestEntityTooLargeHttpException;
use OAT\SimpleRoster\Request\ParamConverter\BulkOperationCollectionParamConverter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class BulkOperationCollectionParamConverterTest extends TestCase
{
    private BulkOperationCollectionParamConverter $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new BulkOperationCollectionParamConverter();
    }

    public function testItReturnsEmptyForUnsupportedClass(): void
    {
        $argument = new ArgumentMetadata('wrong', \stdClass::class, false, false, null);

        $result = $this->subject->resolve(new Request(), $argument);

        self::assertEmpty(iterator_to_array($result, false));
    }

    public function testItThrowsBadRequestForInvalidJson(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid JSON request body received.');

        $argument = new ArgumentMetadata('bulkCollection', BulkOperationCollection::class, false, false, null);

        $request = Request::create(
            '/test',
            'POST',
            [],
            [],
            [],
            [],
            '{invalid-json'
        );

        iterator_to_array($this->subject->resolve($request, $argument));
    }

    public function testItThrowsBadRequestForEmptyBody(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Empty request body received.');

        $argument = new ArgumentMetadata('bulkCollection', BulkOperationCollection::class, false, false, null);

        $request = Request::create(
            '/test',
            Request::METHOD_POST,
            [],
            [],
            [],
            [],
            '[]'
        );

        iterator_to_array($this->subject->resolve($request, $argument), false);
    }

    public function testItThrowsRequestEntityTooLargeWhenLimitExceeded(): void
    {
        $this->expectException(RequestEntityTooLargeHttpException::class);
        $this->expectExceptionMessage('Bulk operation limit has been exceeded');

        $argument = new ArgumentMetadata('bulkCollection', BulkOperationCollection::class, false, false, null);

        $ops = [];
        for ($i = 0; $i < BulkOperationCollectionParamConverter::BULK_OPERATIONS_LIMIT + 1; $i++) {
            $ops[] = ['identifier' => 'user' . $i];
        }

        $request = Request::create(
            '/test',
            'POST',
            [],
            [],
            [],
            [],
            json_encode($ops, JSON_THROW_ON_ERROR)
        );

        iterator_to_array($this->subject->resolve($request, $argument));
    }

    public function testItResolvesBulkOperationCollectionForPostAsCreate(): void
    {
        $argument = new ArgumentMetadata('bulkCollection', BulkOperationCollection::class, false, false, null);

        $requestBodyContent = json_encode([
            ['identifier' => 'user1'],
            ['identifier' => 'user2', 'attributes' => ['k' => 'v']],
        ], JSON_THROW_ON_ERROR);

        $request = Request::create(
            '/test',
            Request::METHOD_POST,
            [],
            [],
            [],
            [],
            $requestBodyContent
        );

        $result = iterator_to_array($this->subject->resolve($request, $argument), false);

        self::assertCount(1, $result);
        self::assertInstanceOf(BulkOperationCollection::class, $result[0]);

        /** @var BulkOperationCollection $collection */
        $collection = $result[0];

        self::assertCount(2, $collection);

        $items = iterator_to_array($collection, false);

        self::assertSame('user1', $items[0]->getIdentifier());
        self::assertSame(BulkOperation::TYPE_CREATE, $items[0]->getType());
        self::assertSame([], $items[0]->getAttributes());

        self::assertSame('user2', $items[1]->getIdentifier());
        self::assertSame(BulkOperation::TYPE_CREATE, $items[1]->getType());
        self::assertSame(['k' => 'v'], $items[1]->getAttributes());
    }

    public function testItResolvesBulkOperationCollectionForPatchAsUpdate(): void
    {
        $argument = new ArgumentMetadata('bulkCollection', BulkOperationCollection::class, false, false, null);

        $requestBodyContent = json_encode([
            ['identifier' => 'user1'],
        ], JSON_THROW_ON_ERROR);

        $request = Request::create(
            '/test',
            Request::METHOD_PATCH,
            [],
            [],
            [],
            [],
            $requestBodyContent
        );

        $result = iterator_to_array($this->subject->resolve($request, $argument), false);

        /** @var BulkOperationCollection $collection */
        $collection = $result[0];
        $items = iterator_to_array($collection, false);

        self::assertSame(BulkOperation::TYPE_UPDATE, $items[0]->getType());
    }
}
