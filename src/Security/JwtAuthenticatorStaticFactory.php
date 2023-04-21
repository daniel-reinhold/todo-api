<?php

namespace App\Security;

use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class JwtAuthenticatorStaticFactory
{
    public static function createJwtAuthenticator(
        UserRepository $userRepository,
        JWTTokenManagerInterface $jwtTokenManager
    ): JwtAuthenticator {
        return new JwtAuthenticator(
            userRepository: $userRepository,
            jwtTokenManager: $jwtTokenManager
        );
    }
}