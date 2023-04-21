<?php

namespace App\Security;

use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class JwtAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly JWTTokenManagerInterface $jwtTokenManager
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->headers->has("Authorization");
    }

    public function authenticate(Request $request): Passport
    {
        if ($request->headers->has("Authorization")) {
            $token = $request->headers->get('Authorization');

            try {
                $tokenData = $this->jwtTokenManager->parse($token);
                $userId = null;

                if (array_key_exists("user_id", $tokenData)) {
                    $userId = $tokenData['user_id'];
                }

                if ($userId != null) {
                    return new Passport(
                        userBadge: new UserBadge(
                            userIdentifier: (string) $userId,
                            userLoader: function (string $userIdentifier) {
                                return $this->userRepository->findUserById((int) $userIdentifier);
                            }
                        ),
                        credentials: new PasswordCredentials($token)
                    );
                } else {
                    throw new AuthenticationException(
                        message: 'Invalid token!'
                    );
                }
            } catch (JWTDecodeFailureException $e) {
                throw new AuthenticationException(
                    message: 'Invalid token!'
                );
            }
        }

        throw new AuthenticationException(
            message: 'No token!'
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(
            data: array(
                'status' => 401,
                'message' => $exception->getMessage()
            ),
            status: 401
        );
    }
}