<?php declare(strict_types=1);

namespace App\Model\Denormalizer;

use App\Model\Assignment;
use App\Model\User;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class UserDenormalizer implements DenormalizerInterface
{
    /**
     * @var DenormalizerInterface
     */
    private $denormalizer;

    public function __construct(DenormalizerInterface $denormalizer)
    {
        $this->denormalizer = $denormalizer;
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type !== User::class;
    }

    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $assignmentsDenormalized = [];
        if (!empty($data['assignments'])) {
            foreach ($data['assignments'] as $assignmentNormalized) {
                $assignmentsDenormalized[] = $this->denormalizer->denormalize(
                    $assignmentNormalized,
                    Assignment::class,
                    $format,
                    $context
                );
            }
        }

        return new User($data['login'], $data['password'], $assignmentsDenormalized);
    }
}