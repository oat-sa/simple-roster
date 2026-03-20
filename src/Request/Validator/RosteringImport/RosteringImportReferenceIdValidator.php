<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Request\Validator\RosteringImport;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RosteringImportReferenceIdValidator
{
    private const MAX_REFERENCE_ID_LENGTH = 36;

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
                new Assert\Length(
                    max: self::MAX_REFERENCE_ID_LENGTH,
                    maxMessage: sprintf('Reference ID exceeds max length (%d).', self::MAX_REFERENCE_ID_LENGTH)
                ),
                new Assert\Regex(
                    pattern: '/^(?!.*\.\.)[A-Za-z0-9._-]+$/',
                    message: 'Reference ID contains unsupported characters.'
                ),
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
