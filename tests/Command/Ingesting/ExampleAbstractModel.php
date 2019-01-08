<?php

namespace App\Tests\Command\Ingesting;

use App\Model\AbstractModel;

class ExampleAbstractModel extends AbstractModel
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $mandatory_prop_1;
    /**
     * @var string
     */
    private $mandatory_prop_2;
    /**
     * @var string
     */
    private $optional_prop_1;

    /**
     * @inheritdoc
     */
    public static function createFromArray(array $data): AbstractModel
    {
        $model = new self();
        $model->name = $data['name'] ?? null;
        $model->mandatory_prop_1 = $data['mandatory_prop_1'] ?? null;
        $model->mandatory_prop_2 = $data['mandatory_prop_2'] ?? null;
        $model->optional_prop_1 = $data['optional_prop_1'] ?? null;
        return $model;
    }

    /**
     * @inheritdoc
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'mandatory_prop_1' => $this->mandatory_prop_1,
            'mandatory_prop_2' => $this->mandatory_prop_2,
            'optional_prop_1' => $this->optional_prop_1,
        ];
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function validate(): void
    {
        if (!$this->name) {
            $this->throwExceptionRequiredFieldEmpty('name');
        }
        if (!$this->mandatory_prop_1) {
            $this->throwExceptionRequiredFieldEmpty('mandatory_prop_1');
        }
        if (!$this->mandatory_prop_2) {
            $this->throwExceptionRequiredFieldEmpty('mandatory_prop_2');
        }
    }
}
