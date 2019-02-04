<?php

namespace App\Service;

use App\Lti\LaunchRequestBuilder;
use App\Model\Infrastructure;
use App\Model\LineItem;
use App\Model\User;
use App\ODM\ItemManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Security;

class LtiLinkProvider
{
    use AssignmentRetrieverTrait;

    /**
     * @var Security
     */
    private $security;

    /**
     * @var ItemManagerInterface
     */
    private $itemManager;

    /**
     * @var LaunchRequestBuilder
     */
    private $launchRequestBuilder;

    public function __construct(Security $security, ItemManagerInterface $itemManager, LaunchRequestBuilder $launchRequestBuilder)
    {
        $this->security = $security;
        $this->itemManager = $itemManager;
        $this->launchRequestBuilder = $launchRequestBuilder;
    }

    /**
     * @param string $id
     * @return array
     * @throws \Exception
     */
    public function provideLtiRequestParametersForAssignmentId(string $id): array
    {
        /** @var User $user */
        $user = $this->security->getUser();
        if (null === $user) {
            throw new \RuntimeException('User is not logged in.');
        }

        $assignment = $this->getAssignmentById($user, $id);
        if ($assignment === null) {
            throw new NotFoundHttpException(sprintf('Assignment with ID %d has not been found', $id));
        }

        /** @var LineItem $lineItem */
        $lineItem = $this->itemManager->load(LineItem::class, $assignment->getLineItemTaoUri());
        if ($lineItem === null) {
            throw new \Exception(sprintf('Line item "%s" has disappeared.', $lineItem->getTaoUri()));
        }

        /** @var Infrastructure $infrastructure */
        $infrastructure = $this->itemManager->load(Infrastructure::class, $lineItem->getInfrastructureId());
        if ($infrastructure === null) {
            throw new \Exception(sprintf('Infrastructure "%s" has disappeared.', $infrastructure->getId()));
        }

        $ltiRequestParameters = $this->launchRequestBuilder->build($user, $lineItem, $infrastructure);

        return $ltiRequestParameters;
    }
}