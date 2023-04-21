<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Security\JwtAuthenticator;
use App\Utils\ResponseUtils;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{
    public function changeFirstName(
        Request $request,
        JwtAuthenticator $authenticator,
        UserRepository $userRepository,
        ResponseUtils $responseUtils
    ): JsonResponse {
        $userId = $authenticator->authenticate($request)->getUser()->getUserIdentifier();
        $user = $userRepository->findUserById((int) $userId);

        $updatedValue = $request->query->get("value");

        if ($updatedValue == null || strlen(trim($updatedValue)) < 1) {
            return $responseUtils->errorResponse(
                message: 'Bitte gib deinen neuen Vornamen an'
            );
        }

        $user->setFirstName($updatedValue);
        $user->setUpdatedAt(new DateTime());
        $userRepository->save($user, true);

        return new JsonResponse(
            data: [
                'message' => 'Dein Vorname wurde aktualisiert',
                'user' => $user->asJsonObject()
            ],
            status: 200
        );
    }

    public function changeLastName(
        Request $request,
        JwtAuthenticator $authenticator,
        UserRepository $userRepository,
        ResponseUtils $responseUtils
    ): JsonResponse {
        $userId = $authenticator->authenticate($request)->getUser()->getUserIdentifier();
        $user = $userRepository->findUserById((int) $userId);

        $updatedValue = $request->query->get("value");

        if ($updatedValue == null || strlen(trim($updatedValue)) < 1) {
            return $responseUtils->errorResponse(
                message: 'Bitte gib deinen neuen Nachnamen an'
            );
        }

        $user->setLastName($updatedValue);
        $user->setUpdatedAt(new DateTime());
        $userRepository->save($user, true);

        return new JsonResponse(
            data: [
                'message' => 'Dein Nachname wurde aktualisiert',
                'user' => $user->asJsonObject()
            ],
            status: 200
        );
    }

    public function changeDateOfBirth(
        Request $request,
        JwtAuthenticator $authenticator,
        UserRepository $userRepository,
        ResponseUtils $responseUtils
    ): JsonResponse {
        $userId = $authenticator->authenticate($request)->getUser()->getUserIdentifier();
        $user = $userRepository->findUserById((int) $userId);

        $updatedValue = $request->query->get("value");

        if ($updatedValue == null || strlen(trim($updatedValue)) < 1) {
            return $responseUtils->errorResponse(
                message: 'Bitte gib dein neues Geburtsdatum an'
            );
        }

        $dateOfBirthAsDate = DateTime::createFromFormat('Y-m-d', trim($updatedValue));

        if ($dateOfBirthAsDate) {
            $dateOfBirthAsDate->setTime(hour: 0, minute: 0);
        } else {
            return $responseUtils->errorResponse('Bitte Überprüfe dein neues Geburtsdatum.');
        }

        $user->setDateOfBirth($dateOfBirthAsDate);
        $user->setUpdatedAt(new DateTime());
        $userRepository->save($user, true);

        return new JsonResponse(
            data: [
                'message' => 'Dein Geburtsdatum wurde aktualisiert',
                'user' => $user->asJsonObject()
            ],
            status: 200
        );
    }

    public function changePassword(
        Request $request,
        JwtAuthenticator $authenticator,
        UserRepository $userRepository,
        ResponseUtils $responseUtils,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $userId = $authenticator->authenticate($request)->getUser()->getUserIdentifier();
        $user = $userRepository->findUserById((int) $userId);

        $updatedPassword = $request->query->get("password");
        $passwordConfirmation = $request->query->get("password_confirmation");

        if ($updatedPassword == null || strlen(trim($updatedPassword)) < 1) {
            return $responseUtils->errorResponse(
                message: 'Bitte gib dein neues Passwort an'
            );
        }

        if ($passwordConfirmation == null || strlen(trim($passwordConfirmation)) < 1) {
            return $responseUtils->errorResponse(
                message: 'Bitte bestätige dein neues Passwort'
            );
        }

        if (trim($updatedPassword) != trim($passwordConfirmation)) {
            return $responseUtils->errorResponse('Dein Passwort und die Passwortbestätigung stimmen nicht überein.');
        }

        if (!preg_match("#[0-9]+#", $updatedPassword)) {
            return $responseUtils->errorResponse('Dein neues Passwort nuss mindestens eine Zahl beinhalten.');
        }

        if (!preg_match("#[A-Z]+#", $updatedPassword)) {
            return $responseUtils->errorResponse('Dein neues Passwort nuss mindestens einen Großbuchstaben beinhalten.');
        }

        if (!preg_match("#[a-z]+#", $updatedPassword)) {
            return $responseUtils->errorResponse('Dein neues Passwort nuss mindestens einen Kleinbuchstabens beinhalten.');
        }

        $user->setPassword(
            $passwordHasher->hashPassword(
                user: $user,
                plainPassword: $updatedPassword
            )
        );
        $user->setUpdatedAt(new DateTime());
        $userRepository->save($user, true);

        return new JsonResponse(
            data: [
                'message' => 'Dein Passwort wurde aktualisiert',
                'user' => $user->asJsonObject()
            ],
            status: 200
        );
    }

    public function deleteUser(
        Request $request,
        JwtAuthenticator $authenticator,
        UserRepository $userRepository,
    ): JsonResponse {
        $userId = $authenticator->authenticate($request)->getUser()->getUserIdentifier();
        $user = $userRepository->findUserById((int) $userId);

        $user->setDeleted(true);
        $user->setDeletedAt(new DateTime());
        $userRepository->save($user, true);

        return new JsonResponse(
            data: [
                'message' => 'Dein Account wurde gelöscht',
                'user' => $user->asJsonObject()
            ],
            status: 200
        );
    }

}
