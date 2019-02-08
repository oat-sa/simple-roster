<?php declare(strict_types=1);

namespace App\Tests\Unit\Generator;

use App\Generator\NonceGenerator;
use Carbon\Carbon;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;

class NonceGeneratorTest extends TestCase
{
    public function testItGeneratesUniqueNonce(): void
    {
        $subject = new NonceGenerator();

        Carbon::setTestNow(Carbon::create(2019, 1, 1));
        $nonce1 = $subject->generate();

        Carbon::setTestNow(Carbon::create(2019, 1, 2));
        $nonce2 = $subject->generate();

        $this->assertNotEquals($nonce1, $nonce2);
    }
}
