<?php declare(strict_types=1);

namespace App\Validation;

use App\Model\ModelInterface;
use App\Model\LineItem;
use App\Model\User;
use App\ModelManager\InfrastructureManager;
use App\ModelManager\LineItemManager;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserValidator extends ModelValidator
{
    /**
     * @var LineItemManager
     */
    private $lineItemManager;

    public function __construct(LineItemManager $lineItemManager, ValidatorInterface $validator)
    {
        parent::__construct($validator);

        $this->lineItemManager = $lineItemManager;
    }

    /**
     * @param ModelInterface $user
     * @throws ValidationException
     */
    public function validate(ModelInterface $user): void
    {
        parent::validate($user);

        if (!$user instanceof User) {
            return;
        }

        foreach ($user->getAssignments() as $assignment) {
            parent::validate($assignment);

            $lineItemTaoUri = $assignment->getLineItemTaoUri();
            if ($this->lineItemManager->read($lineItemTaoUri) === null) {
                throw new ValidationException(sprintf('Line item with tao uri "%s" not found', $lineItemTaoUri));
            }
        }
    }
}