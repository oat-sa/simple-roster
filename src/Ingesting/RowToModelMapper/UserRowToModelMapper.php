<?php declare(strict_types=1);

namespace App\Ingesting\RowToModelMapper;

use App\Model\ModelInterface;
use App\Model\Assignment;
use App\Model\User;
use App\ODM\Id\IdGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserRowToModelMapper extends AbstractRowToModelMapper
{
    private $idGenerator;
    private $passwordEncoder;

    public function __construct(IdGeneratorInterface $idGenerator, UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->idGenerator = $idGenerator;
        $this->passwordEncoder = $passwordEncoder;
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
            $id = $this->idGenerator->generate($fieldValues['username']);
            $newAssignment = new Assignment($id, $assignmentUri);
            $assignments[] = $newAssignment;
        }
        unset($fieldValues['assignments']);

        /** @var User $user */
        $user = new User($fieldValues['username'], $assignments);

        // encrypt user password
        $encodedPassword = $this->passwordEncoder->encodePassword($user, $fieldValues['password']);

        $user->setPassword($encodedPassword);

        return $user;
    }
}