<?php declare(strict_types=1);

namespace App\Model\Storage;

use App\Model\AbstractModel;
use App\Model\Denormalizer\UserDenormalizer;
use App\Model\User;
use App\Storage\StorageInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class UserStorage extends AbstractModelStorage
{
    public function __construct(StorageInterface $storage, NormalizerInterface $normalizer, UserDenormalizer $denormalizer)
    {
        $this->storage = $storage;
        $this->normalizer = $normalizer;
        $this->denormalizer = $denormalizer;

        parent::__construct($storage, $normalizer, $denormalizer);
    }

    protected function getTable(): string
    {
        return 'users';
    }

    protected function getKeyFieldName(): string
    {
        return 'login';
    }

    /**
     * @param AbstractModel $model
     * @return string
     * @throws \Exception
     */
    public function getKey(AbstractModel $model): string
    {
        /** @var User $model */
        $this->assertModelClass($model);

        return $model->getLogin();
    }

    protected function getModelClass(): string
    {
        return User::class;
    }
}