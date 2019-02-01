<?php

namespace App\Entity;

class Infrastructure implements EntityInterface
{
    /** @var int */
    private $id;

    /** @var string */
    private $label;

    /** @var string */
    private $ltiDirectorLink;

    /** @var string */
    private $ltiKey;

    /** @var string */
    private $ltiSecret;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getLtiDirectorLink(): ?string
    {
        return $this->ltiDirectorLink;
    }

    public function setLtiDirectorLink(string $ltiDirectorLink): self
    {
        $this->ltiDirectorLink = $ltiDirectorLink;

        return $this;
    }

    public function getLtiKey(): ?string
    {
        return $this->ltiKey;
    }

    public function setLtiKey(string $ltiKey): self
    {
        $this->ltiKey = $ltiKey;

        return $this;
    }

    public function getLtiSecret(): ?string
    {
        return $this->ltiSecret;
    }

    public function setLtiSecret(string $ltiSecret): self
    {
        $this->ltiSecret = $ltiSecret;

        return $this;
    }
}
