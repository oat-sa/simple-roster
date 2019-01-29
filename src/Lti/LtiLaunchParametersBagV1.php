<?php

namespace App\Lti;

class LtiLaunchParametersBagV1 extends AbstractLtiParametersBag
{
    /**
     * @var string
     */
    private $ltiMessageType = 'basic-lti-launch-request';

    /**
     * @var string
     */
    private $resourceLinkId = 'Launch Title';

    /**
     * @var string
     */
    private $resourceLinkTitle = 'Launch Title';

    /**
     * @var string
     */
    private $resourceLinkLabel = 'Launch label';

    /**
     * @var string
     */
    private $contextId = 'Service call ID';

    /**
     * @var string
     */
    private $contextTitle = 'Launch Title';

    /**
     * @var string
     */
    private $contextLabel = 'Launch label';

    /**
     * @var string
     */
    private $userId;

    /**
     * @var string
     */
    private $roles = 'Learner';

    /**
     * @var string
     */
    private $lisPersonNameFull;

    public function __construct(string $userId, string $resourceLinkId)
    {
        $this->userId = $userId;
        $this->resourceLinkId = $resourceLinkId;
    }
}
