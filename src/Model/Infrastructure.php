<?php

namespace App\Model;

class Infrastructure extends Model
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

    /**
     * @inheritdoc
     */
    static public function createFromArray(array $data): Model
    {
        $model = new self();
        $model->id = $data['id'] ?? null;
        $model->ltiDirectorLink = $data['lti_director_link'] ?? null;
        $model->key = $data['key'] ?? null;
        $model->secret = $data['secret'] ?? null;
        return $model;
    }

    /**
     * @inheritdoc
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'lti_director_link' => $this->ltiDirectorLink,
            'key' => $this->key,
            'secret' => $this->secret,
        ];
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
