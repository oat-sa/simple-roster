<?php

namespace App\ODM\Validator\Constraints;

use App\ODM\ItemManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ExistingItemValidator extends ConstraintValidator
{
    private $itemManager;

    public function __construct(ItemManagerInterface $itemManager)
    {
        $this->itemManager = $itemManager;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ExistingItem) {
            throw new UnexpectedTypeException($constraint, ExistingItem::class);
        }

        // custom constraints should ignore null and empty values to allow
        // other constraints (NotBlank, NotNull, etc.) take care of that
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        if (!$this->itemManager->isExistById($constraint->itemClass, $value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ item_class }}', $constraint->itemClass)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}