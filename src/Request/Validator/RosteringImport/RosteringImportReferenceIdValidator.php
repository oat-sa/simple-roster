<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Request\Validator\RosteringImport;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RosteringImportReferenceIdValidator
{
    public function __construct(private readonly ValidatorInterface $validator)
    {
    }

    public function validate(string $referenceId): string
    {
        $trimmedReferenceId = trim($referenceId);
        $errors = $this->validator->validate(
            $trimmedReferenceId,
            [
                new Assert\NotBlank(message: 'Reference ID cannot be empty.'),
                new Assert\Uuid(message: 'Reference ID must be a valid UUID.'),
            ]
        );

        if ($errors->count() > 0) {
            /** @var ConstraintViolationInterface $firstError */
            $firstError = $errors[0];
            throw new BadRequestHttpException($firstError->getMessage());
        }

        return $trimmedReferenceId;
    }
}
