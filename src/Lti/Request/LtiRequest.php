<?php declare(strict_types=1);

namespace App\Lti\Request;

use JsonSerializable;

class LtiRequest implements JsonSerializable
{
    public const LTI_MESSAGE_TYPE = 'basic-lti-launch-request';
    public const LTI_VERSION = 'LTI-1p0';
    public const LTI_CONTEXT_TYPE = 'CourseSection';
    public const LTI_ROLE = 'Learner';

    /** @var string */
    private $link;

    /** @var array  */
    private $parameters;

    public function __construct(string $link, array $parameters)
    {
        $this->link = $link;
        $this->parameters = $parameters;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function jsonSerialize()
    {
        return [
            'ltiLink' => $this->link,
            'ltiParams' => $this->parameters,
        ];
    }
}
