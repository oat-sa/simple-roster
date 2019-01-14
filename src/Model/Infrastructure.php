<?php declare(strict_types=1);

namespace App\Model;

class Infrastructure implements ModelInterface
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

    public function getLtiDirectorLink(): string
    {
        return $this->ltiDirectorLink;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }
}
