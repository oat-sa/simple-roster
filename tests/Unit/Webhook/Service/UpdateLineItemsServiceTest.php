<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Webhook\Service;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Repository\LineItemRepository;
use OAT\SimpleRoster\WebHook\Service\UpdateLineItemsService;
use OAT\SimpleRoster\WebHook\UpdateLineItemCollection;
use OAT\SimpleRoster\WebHook\UpdateLineItemDto;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UpdateLineItemsServiceTest extends TestCase
{
    /** @var EntityManagerInterface|MockObject */
    private $entityManager;

    /** @var LineItemRepository|MockObject */
    private $lineItemRepository;

    /** @var MockObject|LoggerInterface */
    private $logger;

    private UpdateLineItemsService $subject;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->lineItemRepository = $this->createMock(LineItemRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->subject = new UpdateLineItemsService(
            $this->lineItemRepository,
            $this->entityManager,
            $this->logger
        );
    }

    public function testItIgnoresUnknownUpdates(): void
    {
        $this->lineItemRepository->expects(self::never())
            ->method('findBy');
        $this->entityManager->expects(self::never())
            ->method('persist');

        $updateLineItemCollection = new UpdateLineItemCollection(
            new UpdateLineItemDto(
                '52a3de8dd0f270fd193f9f4bff05232f',
                'Unknown',
                'https://tao.instance/ontologies/tao.rdf#i5fb5',
                (new DateTimeImmutable())->setTimestamp(1565602371),
                'qti-interactions-delivery'
            )
        );

        $result = $this->subject->handleUpdates($updateLineItemCollection);

        /** @var UpdateLineItemDto $updateLineItemDto */
        $updateLineItemDto = $result->getIterator()[0];

        self::assertSame('ignored', $updateLineItemDto->getStatus());
    }

    public function testItAcceptsUpdate(): void
    {
        $this->lineItemRepository->expects(self::once())
            ->method('findBy')
            ->with(
                [
                    'slug' => ['qti-interactions-delivery']
                ]
            )
            ->willReturn(
                [
                    (new LineItem())
                        ->setSlug('qti-interactions-delivery')
                        ->setUri('http://lineitemuri.com')
                ]
            );

        $this->entityManager->expects(self::once())
            ->method('persist');

        $this->logger->expects(self::once())
            ->method('info')
            ->with(
                'The line item id 0 was updated',
                [
                    'oldUri' => 'http://lineitemuri.com',
                    'newUri' => 'https://tao.instance/ontologies/tao.rdf#i5fb5'
                ]
            );

        $this->entityManager->expects(self::once())
            ->method('flush');

        $updateLineItemCollection = new UpdateLineItemCollection(
            new UpdateLineItemDto(
                '52a3de8dd0f270fd193f9f4bff05232f',
                'oat\\taoPublishing\\model\\publishing\\event\\RemoteDeliveryCreatedEvent',
                'https://tao.instance/ontologies/tao.rdf#i5fb5',
                (new DateTimeImmutable())->setTimestamp(1565602371),
                'qti-interactions-delivery'
            )
        );

        $result = $this->subject->handleUpdates($updateLineItemCollection);

        /** @var UpdateLineItemDto $updateLineItemDto */
        $updateLineItemDto = $result->getIterator()[0];

        self::assertSame('accepted', $updateLineItemDto->getStatus());
    }

    public function testItIgnoresDuplicatedUpdates(): void
    {
        $lineItem = (new LineItem())
            ->setSlug('qti-interactions-delivery');

        $this->lineItemRepository->expects(self::once())
            ->method('findBy')
            ->with(
                [
                    'slug' => ['qti-interactions-delivery', 'qti-interactions-delivery']
                ]
            )
            ->willReturn(
                [
                    $lineItem
                ]
            );

        $this->entityManager->expects(self::once())
            ->method('persist')
            ->with(
                $lineItem->setUri('https://tao.instance/ontologies/tao.rdf#i5fb5')
            );

        $this->logger->expects(self::at(0))
            ->method('info')
            ->with(
                'The line item id 0 was updated',
                [
                    'oldUri' => 'https://tao.instance/ontologies/tao.rdf#i5fb5',
                    'newUri' => 'https://tao.instance/ontologies/tao.rdf#i5fb5'
                ]
            );

        $this->entityManager->expects(self::once())->method('flush');

        $updateLineItemCollection = new UpdateLineItemCollection(
            new UpdateLineItemDto(
                '111',
                'oat\\taoPublishing\\model\\publishing\\event\\RemoteDeliveryCreatedEvent',
                'https://tao.instance/ontologies/tao.rdf#i5fb5',
                (new DateTimeImmutable())->setTimestamp(1565602380),
                'qti-interactions-delivery'
            ),
            new UpdateLineItemDto(
                '222',
                'oat\\taoPublishing\\model\\publishing\\event\\RemoteDeliveryCreatedEvent',
                'https://tao.instance/ontologies/tao.rdf#duplicated',
                (new DateTimeImmutable())->setTimestamp(1565602371),
                'qti-interactions-delivery'
            )
        );

        $result = $this->subject->handleUpdates($updateLineItemCollection);

        /** @var UpdateLineItemDto $updateLineItemDto */
        $updateLineItemDto = $result->getIterator()[0];

        /** @var UpdateLineItemDto $updateLineItemDto */
        $updateLineItemDtoDuplicated = $result->getIterator()[1];

        self::assertSame('accepted', $updateLineItemDto->getStatus());
        self::assertSame('ignored', $updateLineItemDtoDuplicated->getStatus());
    }

    public function testErrorForUpdatesWithNotFoundSlug(): void
    {
        $this->lineItemRepository->expects(self::once())
            ->method('findBy')
            ->with(
                [
                    'slug' => ['qti-interactions-delivery']
                ]
            )
            ->willReturn([]);

        $this->entityManager->expects(self::never())->method('persist');

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Impossible to update the line item. The slug qti-interactions-delivery does not exist.',
                [
                    'updateId' => '52a3de8dd0f270fd193f9f4bff05232f'
                ]
            );

        $this->entityManager->expects(self::once())->method('flush');

        $updateLineItemCollection = new UpdateLineItemCollection(
            new UpdateLineItemDto(
                '52a3de8dd0f270fd193f9f4bff05232f',
                'oat\\taoPublishing\\model\\publishing\\event\\RemoteDeliveryCreatedEvent',
                'https://tao.instance/ontologies/tao.rdf#i5fb5',
                (new DateTimeImmutable())->setTimestamp(1565602371),
                'qti-interactions-delivery'
            )
        );

        $result = $this->subject->handleUpdates($updateLineItemCollection);

        /** @var UpdateLineItemDto $updateLineItemDto */
        $updateLineItemDto = $result->getIterator()[0];

        self::assertSame('error', $updateLineItemDto->getStatus());
    }
}
