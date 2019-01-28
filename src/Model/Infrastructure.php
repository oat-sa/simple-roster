<?php declare(strict_types=1);

namespace App\Model;

use App\ODM\Annotations\Item;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Item(table="infrastructures", primaryKey="id")
 */
class Infrastructure implements ModelInterface
{
    /**
     * @var string
     *
     * @Assert\NotBlank
     */
    private $id;

    /**
     * @var string
     *
     * @Assert\NotBlank
     */
    private $ltiDirectorLink;

    /**
     * @var string
     *
     * @Assert\NotBlank
     */
    private $key;

    /**
     * @var string
     *
     * @Assert\NotBlank
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
