<?php

namespace OAT\SimpleRoster\Service\JWT;

use Carbon\Carbon;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token;
use Symfony\Component\Security\Core\User\UserInterface;

class TokenGenerator
{
    //REGISTERED CLAIMS
    private const IDENTIFIEDBY_CLAIM = 'jti';
    private const ISSUEDAT_CLAIM = 'iat';
    private const EXPIRATION_CLAIM = 'exp';

    //CUSTOM CLAIMS
    private const IDENTIFIER_CLAIM = 'username';
    private const ROLES_CLAIM = 'roles';

    /** @var string */
    private $privateKeyPath;

    /** @var string */
    private $passphrase;

    public function __construct(
        string $privateKeyPath,
        string $passphrase
    ) {
        $this->privateKeyPath = $privateKeyPath;
        $this->passphrase = $passphrase;
    }

    public function create(UserInterface $user, int $ttl): Token
    {
        $payload = [];

        $payload[self::IDENTIFIER_CLAIM] = $user->getUsername();
        $payload[self::ROLES_CLAIM] = $user->getRoles();

        //Identified by claim
        $payload[self::IDENTIFIEDBY_CLAIM] = $user->getUsername();

        $now = Carbon::now()->unix();
        //Issued at claim
        $payload[self::ISSUEDAT_CLAIM] = $now;
        //Expiration time claim
        $expiration = $now + $ttl;
        $payload[self::EXPIRATION_CLAIM] = $expiration;

        $generatedToken = $this->generateJWTString($payload);

        return $generatedToken;
    }

    private function generateJWTString(array $payload): Token
    {
        $tokenObject = (new Builder());

        foreach ($payload as $payloadKey => $payloadVal) {
            $tokenObject->withClaim($payloadKey, $payloadVal);
        }

        return $tokenObject->getToken(
            new Sha256(),
            new Key(
                $this->privateKeyPath,
                $this->passphrase
            )
        );
    }
}
