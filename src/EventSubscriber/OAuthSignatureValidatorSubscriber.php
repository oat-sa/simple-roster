<?php declare(strict_types=1);

namespace App\EventSubscriber;

use App\Controller\ApiV1\OAuthSignatureValidatedController;
use App\Model\OAuth\Signature;
use App\Security\OAuth\SignatureGenerator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class OAuthSignatureValidatorSubscriber implements EventSubscriberInterface
{
    /**
     * @param FilterControllerEvent $event
     * @throws \OAuthException
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();

        /*
        * $controller passed can be either a class or a Closure.
        * This is not usual in Symfony but it may happen.
        * If it is a class, it comes in array format
        */
        if (!is_array($controller)) {
            return;
        }

        if ($controller[0] instanceof OAuthSignatureValidatedController) {
            $request = $event->getRequest();
            $signature = new Signature(
                //TODO: read the parameters from the Authorization header instead of the query parameters
                $request->query->get('oauth_body_hash'),
                $request->query->get('oauth_consumer_key'),
                $request->query->get('oauth_nonce'),
                $request->query->get('oauth_signature_method'),
                $request->query->get('oauth_timestamp'),
                $request->query->get('oauth_version')
            );

            $signatureGenerator = new SignatureGenerator(
                $signature,
                $request->getSchemeAndHttpHost() . explode('?', $request->getRequestUri())[0],
                $request->getMethod()
            );

            // TODO: The secret should get read from the database (Infrastructure)
            if ($signatureGenerator->getSignature('secret') !== $request->query->get('oauth_signature')) {
                throw new \Exception('Signature is invalid');
            }
        }
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}
