<?php

namespace App\Model;

class LineItem extends AbstractModel
{
    /**
     * @var string
     */
    private $taoUri;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string|null
     */
    private $startDateTime;

    /**
     * @var string|null
     */
    private $endDateTime;

    /**
     * @var string
     */
    private $infrastructureId;

    /**
     * @inheritdoc
     */
    public static function createFromArray(array $data): AbstractModel
    {
        $model = new self();
        $model->taoUri = $data['tao_uri'] ?? null;
        $model->title = $data['title'] ?? null;
        $model->infrastructureId = $data['infrastructure_id'] ?? null;
        $model->startDateTime = $data['start_date_time'] ?? null;
        $model->endDateTime = $data['end_date_time'] ?? null;
        return $model;
    }

    /**
     * @inheritdoc
     */
    public function toArray(): array
    {
        return [
            'tao_uri' => $this->taoUri,
            'title' => $this->title,
            'infrastructure_id' => $this->infrastructureId,
            'start_date_time' => $this->startDateTime,
            'end_date_time' => $this->endDateTime,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getTaoUri(): string
    {
        return $this->taoUri;
    }

    public function getInfrastructureId(): string
    {
        return $this->infrastructureId;
    }

    public function getTitle(): string
    {
        return $this->taoUri;
    }

    public function getStartDateTime(): ?string
    {
        return $this->startDateTime;
    }

    public function getEndDateTime(): ?string
    {
        return $this->endDateTime;
    }

    /**
     * @inheritdoc
     */
    public function validate(): void
    {
        if (!$this->taoUri) {
            $this->throwExceptionRequiredFieldEmpty('tao_uri');
        }
        if (!$this->title) {
            $this->throwExceptionRequiredFieldEmpty('title');
        }
        if (!$this->infrastructureId) {
            $this->throwExceptionRequiredFieldEmpty('infrastructure_id');
        }
    }
}
