<?php declare(strict_types=1);

namespace App\Ingesting\RowToModelMapper;

use App\Model\ModelInterface;
use App\Model\Assignment;
use App\Model\User;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class UserRowToModelMapper extends AbstractRowToModelMapper
{
    private $encoderFactory;

    public function __construct(EncoderFactoryInterface $encoderFactory)
    {
        $this->encoderFactory = $encoderFactory;
    }

    public function map(array $row, array $fieldNames): ModelInterface
    {
        $fieldValues = $this->mapFileLineByFieldNames($row, $fieldNames);

        // collect the remaining elements of line to the single 'assignment' field
        $fieldCount = count($fieldNames);
        $fieldValues['assignments'] = [];
        for ($i = $fieldCount; $i < count($row); $i++) {
            $fieldValues['assignments'][] = $row[$i];
        }

        $assignmentUris = $fieldValues['assignments'];
        $assignments = [];
        foreach ($assignmentUris as $assignmentUri) {
            $assignments[] = new Assignment($assignmentUri);
        }
        unset($fieldValues['assignments']);

        /** @var User $user */
        $user = new User($fieldValues['login'], $fieldValues['password']);

        $user->addAssignments($assignments);

        // encrypt user password
        $encoder = $this->encoderFactory->getEncoder($user);
        $salt = base64_encode(random_bytes(30));
        $encodedPassword = $encoder->encodePassword($user->getPassword(), $salt);
        $user->setPasswordAndSalt($encodedPassword, $salt);

        return $user;
    }
}