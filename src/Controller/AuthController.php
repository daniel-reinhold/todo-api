<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use DateTime;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Utils\ResponseUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class AuthController extends AbstractController
{
    public function login(
        Request $request,
        ResponseUtils $responseUtils,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtTokenManager
    ): JsonResponse {
        $username = (string) $request->query->get("username", "");
        $password = (string) $request->query->get("password", "");

        if (strlen(trim($username)) == 0) {
            return $responseUtils->errorResponse('Bitte gib deinen Benutzernamen an.');
        }

        if (strlen(trim($password)) == 0) {
            return $responseUtils->errorResponse('Bitte gib dein Passwort an.');
        }

        $user = $userRepository->findUserByEmailAddress($username);

        if ($user == null) {
            return $responseUtils->errorResponse(
                message: 'Wir konnten leider keinen Account mit dieser Email-Adresse finden.',
                statusCode: 404
            );
        }

        if (!$passwordHasher->isPasswordValid($user, trim($password))) {
            return $responseUtils->errorResponse(
                message: 'Das eingegebene Passwort ist leider nicht korrekt.',
                statusCode: 401
            );
        }

        $token = $jwtTokenManager->createFromPayload(
            user: $user,
            payload: array("user_id" => $user->getId())
        );

        try {
            $tokenData = $jwtTokenManager->parse($token);
            $expirationTime = 0;

            if (array_key_exists('exp', $tokenData)) {
                $expirationTime = $tokenData['exp'];
            }

            return new JsonResponse(
                data: [
                    'token' => $token,
                    'expiration_time' => $expirationTime,
                    'user' => $user->asJsonObject()
                ],
                status: 200
            );
        } catch (JWTDecodeFailureException $e) {
            throw new AuthenticationException(
                message: 'Token expired!'
            );
        }
    }

    public function register(
        Request $request,
        ResponseUtils $responseUtils,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $firstName = (string) $request->query->get("first_name", "");
        $lastName = (string) $request->query->get("last_name", "");
        $username = (string) $request->query->get("username", "");
        $emailAddress = (string) $request->query->get("email_address", "");
        $dateOfBirth = (string) $request->query->get("date_of_birth", "");
        $password = (string) $request->query->get("password", "");
        $passwordConfirmation = (string) $request->query->get("password_confirmation", "");

        if (strlen(trim($firstName)) == 0) {
            return $responseUtils->errorResponse('Bitte gib deinen Vornamen an.');
        }

        if (strlen(trim($lastName)) == 0) {
            return $responseUtils->errorResponse('Bitte gib deinen Nachnamen an.');
        }

        if (strlen(trim($username)) == 0) {
            return $responseUtils->errorResponse('Bitte gib einen Benutzernamen an.');
        }

        if (strlen(trim($emailAddress)) == 0) {
            return $responseUtils->errorResponse('Bitte gib eine Email-Adresse an an.');
        }

        if (strlen(trim($dateOfBirth)) == 0) {
            return $responseUtils->errorResponse('Bitte gib dein Geburtsdatum an.');
        }

        if (strlen(trim($password)) == 0) {
            return $responseUtils->errorResponse('Bitte gib ein Passwort an.');
        }

        if (strlen(trim($passwordConfirmation)) == 0) {
            return $responseUtils->errorResponse('Bitte bestätige deine Passworteingabe.');
        }

        if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
            return $responseUtils->errorResponse('Bitte gib eine gültige Email-Adresse an.');
        }

        if ($userRepository->doesEmailAddressAlreadyExist($emailAddress)) {
            return $responseUtils->errorResponse('Diese Email-Adresse wird bereits verwendet.');
        }

        if ($userRepository->doesUsernameAlreadyExist($username)) {
            return $responseUtils->errorResponse('Dieser Benutzername ist leider nicht mehr verfügbar.');
        }

        if (trim($password) != trim($passwordConfirmation)) {
            return $responseUtils->errorResponse('Dein Passwort und die Passwortbestätigung stimmen nicht überein.');
        }

        if (strlen(trim($password)) < 8) {
            return $responseUtils->errorResponse('Dein Passwort nuss mindestens 8 Zeichen lang sein.');
        }

        if (!preg_match("#[0-9]+#", $password)) {
            return $responseUtils->errorResponse('Dein Passwort nuss mindestens eine Zahl beinhalten.');
        }

        if (!preg_match("#[A-Z]+#", $password)) {
            return $responseUtils->errorResponse('Dein Passwort nuss mindestens einen Großbuchstaben beinhalten.');
        }

        if (!preg_match("#[a-z]+#", $password)) {
            return $responseUtils->errorResponse('Dein Passwort nuss mindestens einen Kleinbuchstabenxs beinhalten.');
        }

        $dateOfBirthAsDate = DateTime::createFromFormat('Y-m-d', trim($dateOfBirth));

        if ($dateOfBirthAsDate) {
            $dateOfBirthAsDate->setTime(hour: 0, minute: 0);
        } else {
            return $responseUtils->errorResponse('Bitte Überprüfe dein Geburtsdatum.');
        }

        $user = new User();
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setUsername($username);
        $user->setEmailAddress($emailAddress);
        $user->setDateOfBirth($dateOfBirthAsDate);
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $user->setCreatedAt(new DateTime());

        $userRepository->save($user, true);

        return new JsonResponse(
            array(
                "success" => true,
                "user" => $user->asJsonObject()
            )
        );
    }

}
