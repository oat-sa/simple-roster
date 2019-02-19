<?php declare(strict_types=1);

namespace App\Logger;

use App\Request\RequestIdStorage;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Security;

class ExtendedLogProcessor
{
    /** @var Security */
    private $security;

    /** @var SessionInterface */
    private $session;

    /** @var RequestIdStorage */
    private $requestIdStorage;

    public function __construct(
        Security $security,
        SessionInterface $session,
        RequestIdStorage $requestIdStorage
    ) {
        $this->security = $security;
        $this->session = $session;
        $this->requestIdStorage = $requestIdStorage;
    }

    public function __invoke(array $record): array
    {
        $record['extra']['requestId'] = $this->requestIdStorage->getRequestId();
        $record['extra']['sessionId'] = $this->session->getId();
        $record['extra']['username'] = $this->security->getUser() ? $this->security->getUser()->getUsername() : 'guest';

        return $record;
    }
}
