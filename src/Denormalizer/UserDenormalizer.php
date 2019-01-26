<?php declare(strict_types=1);

namespace App\Denormalizer;

use App\Model\Assignment;
use App\Model\User;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;
use Symfony\Component\Serializer\SerializerInterface;

class UserDenormalizer implements DenormalizerInterface
{
    use SerializerAwareTrait;

    /**
     * @var SerializerInterface|DenormalizerInterface
     */
    protected $serializer;

    public function supportsDenormalization($data, $type, $format = null)
    {
        if ($type !== User::class) {
            return false;
        }
        if (empty($data['assignments'])) {
            return true;
        }

        return !current($data['assignments']) instanceof Assignment;
    }

    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (!empty($data['assignments'])) {
            $data['assignments'] = $this->serializer->denormalize($data['assignments'], Assignment::class .'[]');
        }

        return $this->serializer->denormalize($data, $class);
    }
}