<?php

namespace App\Tests\ODM\Validator\Constraints;

use App\ODM\ItemManagerInterface;
use App\ODM\Validator\Constraints\ExistingItem;
use App\ODM\Validator\Constraints\ExistingItemValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ExistingItemValidatorTest extends ConstraintValidatorTestCase
{
    /**
     * @var ItemManagerInterface|MockObject
     */
    protected $itemManager;

    protected function setUp()
    {
        $this->itemManager = $this->createMock(ItemManagerInterface::class);

        parent::setUp();
    }

    protected function createValidator()
    {
        return new ExistingItemValidator($this->itemManager);
    }

    public function testNullIsValid(): void
    {
        $this->validator->validate(null, new ExistingItem());
        $this->assertNoViolation();
    }

    public function testEmptyStringIsValid()
    {
        $this->validator->validate('', new ExistingItem());
        $this->assertNoViolation();
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     */
    public function testExpectsStringCompatibleType()
    {
        $this->validator->validate(new \stdClass(), new ExistingItem());
    }

    public function testItemDoExist()
    {
        $this->itemManager->expects($this->once())
            ->method('isExistById')
            ->with('classOfItem', 'idOfItem')
            ->willReturn(true);

        $this->validator->validate('idOfItem', new ExistingItem(['itemClass' => 'classOfItem']));
        $this->assertNoViolation();
    }

    public function testItemDoesNotExist()
    {
        $itemClass = 'classOfItem';
        $itemId = 'idOfItem';

        $this->itemManager->expects($this->once())
            ->method('isExistById')
            ->with($itemClass, $itemId)
            ->willReturn(false);

        $this->validator->validate('idOfItem', new ExistingItem([
            'itemClass' => $itemClass
        ]));

        $this->buildViolation('The "{{ item_class }}" item with id "{{ value }}" does not exist')
            ->setParameter('{{ item_class }}', $itemClass)
            ->setParameter('{{ value }}', $itemId)
            ->assertRaised();
    }
}