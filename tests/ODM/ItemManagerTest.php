<?php

namespace App\Tests\ODM;

use App\ODM\Annotations\Item;
use App\ODM\Exceptions\ValidationException;
use App\ODM\ItemManager;
use App\ODM\StorageInterface;
use Doctrine\Common\Annotations\Reader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ItemManagerTest extends TestCase
{
    /**
     * @var StorageInterface|MockObject
     */
    private $storage;

    /**
     * @var Reader|MockObject
     */
    private $reader;

    /**
     * @var Serializer|MockObject
     */
    private $serializer;

    /**
     * @var ValidatorInterface|MockObject
     */
    private $validator;

    /**
     * @var ItemManager|MockObject
     */
    private $itemManager;

    /**
     * @var PropertyAccessorInterface|MockObject
     */
    private $propertyAccessor;

    public function setUp()
    {
        $this->storage = $this->createMock(StorageInterface::class);
        $this->reader = $this->createMock(Reader::class);
        $this->serializer = $this->createMock(Serializer::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->propertyAccessor = $this->createMock(PropertyAccessorInterface::class);

        $this->itemManager = new ItemManager($this->storage, $this->reader, $this->serializer, $this->validator, $this->propertyAccessor);
    }

    public function testSaveForValidationError(): void
    {
        $item = new \stdClass();

        $violations = $this->createMock(ConstraintViolationList::class);
        $violations->expects($this->once())
            ->method('count')
            ->willReturn(1);

        $violations->expects($this->once())
            ->method('__toString')
            ->willReturn('error');

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($item)
            ->willReturn($violations);

        $this->expectException(ValidationException::class);

        $this->itemManager->save($item);
    }

    public function testSaveForSuccess(): void
    {
        $pkValue = 'abc123';

        $item = new \stdClass();
        $itemDef = new Item();
        $itemDef->table = 'item_table';
        $itemDef->primaryKey = 'item_pk';

        $violations = $this->createMock(ConstraintViolationList::class);
        $violations->expects($this->once())
            ->method('count')
            ->willReturn(0);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($item)
            ->willReturn($violations);

        $this->reader->expects($this->once())
            ->method('getClassAnnotation')
            ->willReturn($itemDef);

        $normalizedData = ['field1' => 'value1'];
        $this->serializer->expects($this->once())
            ->method('normalize')
            ->with($item)
            ->willReturn($normalizedData);

        $this->propertyAccessor->expects($this->once())
            ->method('getValue')
            ->with($item, $itemDef->primaryKey)
            ->willReturn($pkValue);

        $this->storage->expects($this->once())
            ->method('insert')
            ->with($itemDef->table, [$itemDef->primaryKey => $pkValue], $normalizedData);

        $this->itemManager->save($item);
    }

    public function dataForIsExist()
    {
        return [
            'should_exist' => [['some_filed' => 'some_value'], true],
            'should_not_exist' => [null, false],
        ];
    }

    /**
     * @dataProvider dataForIsExist
     */
    public function testIsItemExist(?array $rawData, bool $expectedResult)
    {
        $item = new \stdClass();
        $pkValue = 'abc123';

        $itemDef = new Item();
        $itemDef->table = 'item_table';
        $itemDef->primaryKey = 'item_pk';

        $this->reader->expects($this->once())
            ->method('getClassAnnotation')
            ->willReturn($itemDef);

        $this->propertyAccessor->expects($this->once())
            ->method('getValue')
            ->with($item, $itemDef->primaryKey)
            ->willReturn($pkValue);

        $this->storage->expects($this->once())
            ->method('read')
            ->with($itemDef->table, [$itemDef->primaryKey => $pkValue])
            ->willReturn($rawData);

        $this->assertSame($expectedResult, $this->itemManager->isExist($item));
    }

    public function testLoadingItem()
    {
        $itemClass = '\stdClass';
        $pkValue = 'abc123';

        $loadedItem = new \stdClass;

        $rawData = ['field' => 'value'];

        $itemDef = new Item();
        $itemDef->table = 'item_table';
        $itemDef->primaryKey = 'item_pk';

        $this->reader->expects($this->once())
            ->method('getClassAnnotation')
            ->willReturn($itemDef);

        $this->storage->expects($this->once())
            ->method('read')
            ->with($itemDef->table, [$itemDef->primaryKey => $pkValue])
            ->willReturn($rawData);

        $this->serializer->expects($this->once())
            ->method('denormalize')
            ->with($rawData, $itemClass)
            ->willReturn($loadedItem);

        $this->assertSame($loadedItem, $this->itemManager->load($itemClass, $pkValue));
    }
}