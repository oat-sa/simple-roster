<?php declare(strict_types=1);

namespace App\ODM;

use App\ODM\Annotations\Item;
use App\ODM\Exceptions\NotAnnotatedException;
use App\ODM\Exceptions\ValidationException;
use Doctrine\Common\Annotations\Reader;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ItemManager implements ItemManagerInterface
{
    private $storage;
    private $annotationReader;
    private $propertyAccessor;
    private $validator;

    /**
     * @var SerializerInterface|NormalizerInterface|DenormalizerInterface
     */
    private $odmSerializer;

    public function __construct(
        StorageInterface $storage,
        Reader $annotationReader,
        SerializerInterface $odmSerializer,
        ValidatorInterface $validator,
        ?PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->storage = $storage;
        $this->annotationReader = $annotationReader;
        $this->odmSerializer = $odmSerializer;
        $this->validator = $validator;
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
    }

    public function load(string $itemClass, string $key): ?object
    {
        $data = $this->loadRawData($this->getItemDefinition($itemClass), $key);

        if (null !== $data) {
            $item = $this->odmSerializer->denormalize($data, $itemClass);
        }

        return $item ?? null;
    }

    private function loadRawData(Item $itemDefinition, string $key): ?array
    {
        return $this->storage->read($itemDefinition->table, [$itemDefinition->primaryKey => $key]);
    }

    /**
     * Checks if the record with same primary key already exists
     */
    public function isExist(object $item): bool
    {
        $itemDefinition = $this->getItemDefinition(get_class($item));

        $data = $this->loadRawData(
            $itemDefinition,
            $this->propertyAccessor->getValue($item, $itemDefinition->primaryKey)
        );

        return $data !== null;
    }

    public function isExistById(string $itemClass, string $key): bool
    {
        return null !== $this->loadRawData($this->getItemDefinition($itemClass), $key);
    }

    public function save(object $item): void
    {
        $this->validate($item);

        $itemDefinition = $this->getItemDefinition(get_class($item));

        $normalizedData = $this->odmSerializer->normalize($item);

        $this->storage->insert(
            $itemDefinition->table,
            [$itemDefinition->primaryKey => $this->propertyAccessor->getValue($item, $itemDefinition->primaryKey)],
            $normalizedData
        );
    }

    public function delete(string $itemClass, string $key): void
    {
        $itemDefinition = $this->getItemDefinition($itemClass);

        $this->storage->delete($itemDefinition->table, [$itemDefinition->primaryKey => $key]);
    }

    private function validate(object $item): void
    {
        $violations = $this->validator->validate($item);

        if ($violations->count() > 0) {
            throw new ValidationException(sprintf('Validation failure on %s', (string) $violations));
        }
    }

    private function getItemDefinition(string $itemClass): Item
    {
        /** @var Item $itemDefinition */
        $itemDefinition = $this->annotationReader->getClassAnnotation(
            new \ReflectionClass($itemClass),
            Item::class
        );

        if (!$itemDefinition) {
            throw new NotAnnotatedException('Class '. $itemClass .' is not configured as an Item');
        }

        return $itemDefinition;
    }

}