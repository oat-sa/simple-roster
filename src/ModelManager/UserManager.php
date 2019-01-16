<?php declare(strict_types=1);

namespace App\ModelManager;

use App\Model\ModelInterface;
use App\Denormalizer\UserDenormalizer;
use App\Model\User;
use App\Storage\StorageInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class UserManager extends AbstractModelManager
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
     * @param ModelInterface $model
     * @return string
     * @throws \Exception
     */
    public function getKey(ModelInterface $model): string
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