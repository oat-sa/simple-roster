<?php

namespace App\Model;

class Infrastructure extends AbstractModel
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $ltiDirectorLink;

    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $secret;

    public function __construct(string $id, string $ltiDirectorLink, string $key, string $secret)
    {
        $this->id = $id;
        $this->ltiDirectorLink = $ltiDirectorLink;
        $this->key = $key;
        $this->secret = $secret;
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function validate(): void
    {
        if (!$this->id) {
            $this->throwExceptionRequiredFieldEmpty('id');
        }
        if (!$this->ltiDirectorLink) {
            $this->throwExceptionRequiredFieldEmpty('lti_director_link');
        }
        if (!$this->key) {
            $this->throwExceptionRequiredFieldEmpty('key');
        }
        if (!$this->secret) {
            $this->throwExceptionRequiredFieldEmpty('secret');
        }
    }
}
