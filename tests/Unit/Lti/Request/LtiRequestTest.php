<?php declare(strict_types=1);

namespace App\Tests\Unit\Lti\Request;

use App\Lti\Request\LtiRequest;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;

class LtiRequestTest extends TestCase
{
    public function testGettersPostConstruction(): void
    {
        $subject = new LtiRequest('link', ['param1', 'param2']);

        $this->assertEquals('link', $subject->getLink());
        $this->assertEquals(['param1', 'param2'], $subject->getParameters());
    }

    public function testJsonSerialization(): void
    {
        $subject = new LtiRequest('link', ['param1', 'param2']);

        $this->assertEquals(
            [
                'ltiLink' => 'link',
                'ltiParams' => ['param1', 'param2']
            ],
            $subject->jsonSerialize()
        );
    }
}
