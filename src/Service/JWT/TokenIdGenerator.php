<?php

namespace OAT\SimpleRoster\Service\JWT;

class TokenIdGenerator
{
    public function generateCacheId(string $identifier): string
    {
        return sprintf('jwt-token.%s', $identifier);
    }
}
