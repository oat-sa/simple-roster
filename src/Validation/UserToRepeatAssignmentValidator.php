<?php declare(strict_types=1);

namespace App\Validation;

use App\Model\ModelInterface;
use App\Model\User;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserToRepeatAssignmentValidator extends ModelValidator
{
    protected $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;

        parent::__construct($validator);
    }

    /**
     * @inheritdoc
     *
     * @throws \Exception
     */
    public function validate(ModelInterface $model): void
    {
        if (!$model instanceof User) {
            throw new \Exception('Validator %s should only be applied to an object of class %s', self::class, User::class);
        }

        parent::validate($model);

        if (count($model->getAssignments()) === 0) {
            throw new ValidationException('User %s does not have any assignments.', $model->getLogin());
        }

        $uniqueLineItemTaoUris = [];
        foreach ($model->getAssignments() as $assignment) {
            if (array_key_exists($assignment->getLineItemTaoUri(), $uniqueLineItemTaoUris)) {
                $uniqueLineItemTaoUris[] = $assignment->getLineItemTaoUri();
            }
        }

        if (count($uniqueLineItemTaoUris) > 1) {
            throw new ValidationException('User %s has assignments for more than one Line Item URI.', $model->getLogin());
        }
    }
}