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
    /** @var LineItemRepository|MockObject */
    private $lineItemRepository;

    /** @var EntityManagerInterface|MockObject */
    private $entityManager;

    /** @var MockObject|LoggerInterface */
    private $logger;

    /** @var UpdateLineItemsService */
    private $sut;

    protected function setUp(): void
    {
        $this->lineItemRepository = $this->createMock(
            LineItemRepository::class
        );

        $this->entityManager = $this->createMock(
            EntityManagerInterface::class
        );

        $this->logger = $this->createMock(
            LoggerInterface::class
        );

        $this->sut = new UpdateLineItemsService(
            $this->lineItemRepository,
            $this->entityManager,
            $this->logger
        );
    }

    public function testItIgnoresUnknownUpdates(): void
    {
        $this->lineItemRepository->expects($this->never())->method('findBy');
        $this->lineItemRepository->expects($this->never())->method('save');

        $updateLineItemCollection = new UpdateLineItemCollection(
            new UpdateLineItemDto(
                '52a3de8dd0f270fd193f9f4bff05232f',
                'Unknown',
                'https://tao.instance/ontologies/tao.rdf#i5fb5',
                (new DateTimeImmutable())->setTimestamp(1565602371),
                'qti-interactions-delivery'
            )
        );

        $result = $this->sut->handleUpdates($updateLineItemCollection);

        /** @var UpdateLineItemDto $updateLineItemDto */
        $updateLineItemDto = $result->getIterator()[0];

        $this->assertSame('ignored', $updateLineItemDto->getStatus());
    }

    public function testItAcceptsUpdate(): void
    {
        $this->lineItemRepository->expects($this->once())
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
                ]
            );

        $this->lineItemRepository->expects($this->once())->method('save');

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'The line item id 0 was updated',
                [
                    'uri' => 'https://tao.instance/ontologies/tao.rdf#i5fb5'
                ]
            );

        $this->entityManager->expects($this->once())->method('flush');

        $updateLineItemCollection = new UpdateLineItemCollection(
            new UpdateLineItemDto(
                '52a3de8dd0f270fd193f9f4bff05232f',
                'RemoteDeliveryPublicationFinished',
                'https://tao.instance/ontologies/tao.rdf#i5fb5',
                (new DateTimeImmutable())->setTimestamp(1565602371),
                'qti-interactions-delivery'
            )
        );

        $result = $this->sut->handleUpdates($updateLineItemCollection);

        /** @var UpdateLineItemDto $updateLineItemDto */
        $updateLineItemDto = $result->getIterator()[0];

        $this->assertSame('accepted', $updateLineItemDto->getStatus());
    }

    public function testItIgnoresDuplicatedUpdates(): void
    {
        $lineItem = (new LineItem())
            ->setSlug('qti-interactions-delivery');

        $this->lineItemRepository->expects($this->once())
            ->method('findBy')
            ->with(
                [
                    'slug' => ['qti-interactions-delivery','qti-interactions-delivery']
                ]
            )
            ->willReturn(
                [
                    $lineItem
                ]
            );

        $this->lineItemRepository->expects($this->once())
            ->method('save')
            ->with(
                $lineItem->setUri('https://tao.instance/ontologies/tao.rdf#i5fb5')
            );

        $this->logger->expects($this->at(0))
            ->method('warning')
            ->with('There are duplicated updates on the request. All of them will be ignore except update id 111. ');

        $this->logger->expects($this->at(1))
        ->method('info')
        ->with(
            'The line item id 0 was updated',
            [
                'uri' => 'https://tao.instance/ontologies/tao.rdf#i5fb5'
            ]
        );

        $this->entityManager->expects($this->once())->method('flush');

        $updateLineItemCollection = new UpdateLineItemCollection(
            new UpdateLineItemDto(
                '111',
                'RemoteDeliveryPublicationFinished',
                'https://tao.instance/ontologies/tao.rdf#i5fb5',
                (new DateTimeImmutable())->setTimestamp(1565602380),
                'qti-interactions-delivery'
            ),
            new UpdateLineItemDto(
                '222',
                'RemoteDeliveryPublicationFinished',
                'https://tao.instance/ontologies/tao.rdf#duplicated',
                (new DateTimeImmutable())->setTimestamp(1565602371),
                'qti-interactions-delivery'
            )
        );

        $result = $this->sut->handleUpdates($updateLineItemCollection);

        /** @var UpdateLineItemDto $updateLineItemDto */
        $updateLineItemDto = $result->getIterator()[0];

        /** @var UpdateLineItemDto $updateLineItemDto */
        $updateLineItemDtoDuplicated = $result->getIterator()[1];

        $this->assertSame('accepted', $updateLineItemDto->getStatus());
        $this->assertSame('ignored', $updateLineItemDtoDuplicated->getStatus());
    }

    public function testErrorForUpdatesWithNotFoundSlug(): void
    {
        $this->lineItemRepository->expects($this->once())
            ->method('findBy')
            ->with(
                [
                    'slug' => ['qti-interactions-delivery']
                ]
            )
            ->willReturn([]);

        $this->lineItemRepository->expects($this->never())->method('save');

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Impossible to update the line item. The slug qti-interactions-delivery does not exist.',
                [
                    'updateId' => '52a3de8dd0f270fd193f9f4bff05232f'
                ]
            );

        $this->entityManager->expects($this->once())->method('flush');

        $updateLineItemCollection = new UpdateLineItemCollection(
            new UpdateLineItemDto(
                '52a3de8dd0f270fd193f9f4bff05232f',
                'RemoteDeliveryPublicationFinished',
                'https://tao.instance/ontologies/tao.rdf#i5fb5',
                (new DateTimeImmutable())->setTimestamp(1565602371),
                'qti-interactions-delivery'
            )
        );

        $result = $this->sut->handleUpdates($updateLineItemCollection);

        /** @var UpdateLineItemDto $updateLineItemDto */
        $updateLineItemDto = $result->getIterator()[0];

        $this->assertSame('error', $updateLineItemDto->getStatus());
    }
}
