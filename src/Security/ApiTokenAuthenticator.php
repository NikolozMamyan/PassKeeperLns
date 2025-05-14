<?php

// src/Security/ApiTokenAuthenticator.php
namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use App\Repository\UserRepository;

class ApiTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(private UserRepository $userRepository) {}

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('X-AUTH-TOKEN');
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $token = $request->headers->get('X-AUTH-TOKEN');
        if (!$token) {
            throw new AuthenticationException('No API token provided');
        }

        return new SelfValidatingPassport(
            new UserBadge($token, function ($token) {
                $user = $this->userRepository->findOneBy(['apiToken' => $token]);
                if (!$user) {
                    throw new AuthenticationException('Invalid API token');
                }
                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(['error' => 'Authentication failed'], Response::HTTP_UNAUTHORIZED);
    }
}
