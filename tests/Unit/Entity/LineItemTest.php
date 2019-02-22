<?php declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\LineItem;
use DateTime;
use PHPUnit\Framework\TestCase;

class LineItemTest extends TestCase
{
    /** @var LineItem */
    private $subject;

    protected function setUp()
    {
        parent::setUp();

        $this->subject = new LineItem();
    }

    public function testItIsAvailableForDateIfStartDateIsNotSet(): void
    {
        $this->assertTrue($this->subject->isAvailableForDate(new DateTime()));
    }

    public function testItIsAvailableForDateIfEndDateIsNotSet(): void
    {
        $this->subject->setStartAt(new DateTime('-3 days'));

        $this->assertTrue($this->subject->isAvailableForDate(new DateTime()));
    }

    public function testItIsAvailableForDateIfDateIsBetweenStartDateAndEndDate(): void
    {
        $this->subject
            ->setStartAt(new DateTime('-1 day'))
            ->setEndAt(new DateTime('+1 day'));

        $this->assertTrue($this->subject->isAvailableForDate(new DateTime()));
    }
}
