<?php

namespace OAT\SimpleRoster\Request\ParamConverter;

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Token;
use OAT\SimpleRoster\Security\Verifier\JwtTokenVerifier;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class TokenParamConverter implements ParamConverterInterface
{
    /** @var JwtTokenVerifier $tokenVerifier */
    private $tokenVerifier;

    public function __construct(JwtTokenVerifier $tokenVerifier)
    {
        $this->tokenVerifier = $tokenVerifier;
    }

    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $decodedRequestBody = json_decode($request->getContent(), true);
        if (!isset($decodedRequestBody['refreshToken'])) {
            throw new BadRequestHttpException("Missing 'refreshToken' in request body.");
        }

        try {
            $refreshToken = (new Parser())->parse($decodedRequestBody['refreshToken']);
            $this->tokenVerifier->isValid($refreshToken);
        } catch (\Throwable $exception) {
            throw new ConflictHttpException('Invalid token.');
        }

        $request->attributes->set($configuration->getName(), $refreshToken);

        return true;
    }

    public function supports(ParamConverter $configuration): bool
    {
        return Token::class === $configuration->getClass();
    }
}
