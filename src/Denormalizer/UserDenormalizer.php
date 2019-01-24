<?php declare(strict_types=1);

namespace App\Denormalizer;

use App\Model\Assignment;
use App\Model\User;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

class UserDenormalizer implements DenormalizerInterface
{
    use SerializerAwareTrait;

    public function supportsDenormalization($data, $type, $format = null)
    {
        if ($type !== User::class) {
            return false;
        }
        if (empty($data['assignments']) || count($data['assignments']) === 0) {
            return false;
        }

        return !current($data['assignments']) instanceof Assignment;
    }

    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $assignmentsDenormalized = [];
        if (!empty($data['assignments'])) {
            foreach ($data['assignments'] as $assignmentNormalized) {
                $assignmentsDenormalized[] = $this->serializer->deserialize($assignmentNormalized, Assignment::class,
                    $format, $context);
            }
            $data['assignments'] = $assignmentsDenormalized;
        }

        return $this->serializer->deserialize($data, $class, $format, $context);
    }
}