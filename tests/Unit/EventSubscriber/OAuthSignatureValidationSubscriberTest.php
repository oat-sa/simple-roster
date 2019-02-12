<?php declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\OAuthSignatureValidationSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelEvents;

class OAuthSignatureValidationSubscriberTest extends TestCase
{
    public function testSubscribedEvents(): void
    {
        $this->assertEquals(
            [KernelEvents::CONTROLLER => 'onKernelController'],
            OAuthSignatureValidationSubscriber::getSubscribedEvents()
        );
    }
}
