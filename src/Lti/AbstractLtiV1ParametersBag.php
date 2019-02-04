<?php

namespace App\Lti;

abstract class AbstractLtiV1ParametersBag implements ParameterBagInterface
{
    /**
     * @var string
     */
    protected $ltiVersion = 'LTI-1p0';

    /**
     * @var string
     */
    protected $toolConsumerInfoProductFamilyCode = 'Roster';

    /**
     * @var string
     */
    protected $toolConsumerInfoVersion = '1.0.0';
}
