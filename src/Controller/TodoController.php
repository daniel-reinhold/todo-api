<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\Todo;
use App\Enum\FilterType;
use App\Enum\SortOrder;
use App\Exception\InvalidFilterTypeException;
use App\Exception\InvalidSortOrderException;
use App\Repository\NotificationRepository;
use App\Repository\TodoRepository;
use App\Repository\UserRepository;
use App\Security\JwtAuthenticator;
use App\Service\FirebaseMessagingService;
use App\Utils\ResponseUtils;
use Beste\Json;
use DateTime;
use Kreait\Firebase\Messaging\CloudMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

const KEY_PARAM_SHOW_DELETED = "show_deleted";
const KEY_PARAM_SHOW_DONE = "show_done";
const KEY_PARAM_START_TIME = "start_time";
const KEY_PARAM_END_TIME = "end_time";
const KEY_PARAM_SORT_BY = "sort_by";
const KEY_PARAM_SORT_ORDER = "sort_order";

class TodoController extends AbstractController
{
    public function getTodos(
        Request $request,
        JwtAuthenticator $authenticator,
        UserRepository $userRepository,
        ResponseUtils $responseUtils
    ): JsonResponse {
        $userId = (int) $authenticator->authenticate($request)->getUser()->getUserIdentifier();
        $user = $userRepository->findUserById($userId);

        $showDeletedString = $request->query->get(KEY_PARAM_SHOW_DELETED, FilterType::Not->toString());
        $showDoneString= $request->query->get(KEY_PARAM_SHOW_DONE, FilterType::Both->toString());
        $startTimeString = $request->query->get(KEY_PARAM_START_TIME);
        $endTimeString = $request->query->get(KEY_PARAM_END_TIME);
        $sortByString = $request->get(KEY_PARAM_SORT_BY, 'created_at');
        $sortOrderString = $request->get(KEY_PARAM_SORT_ORDER, SortOrder::ASC->toString());

        try {
            $showDeleted = FilterType::parse($showDeletedString);
            $showDone = FilterType::parse($showDoneString);

            $startTime = null;
            $endTime = null;

            if ($startTimeString != null && strlen($startTimeString) >= 1) {
                $startTime = DateTime::createFromFormat('Y-m-d\\TH:i', $startTimeString);
            }

            if ($endTimeString != null && strlen($endTimeString) >= 1) {
                $endTime = DateTime::createFromFormat('Y-m-d\\TH:i', $endTimeString);
            }

            if ($startTimeString != null && !$startTime) {
                $responseUtils->errorResponse('Bitte überprüfe das Startdatum deines Filters.');
            }

            if ($endTimeString != null && !$endTime) {
                $responseUtils->errorResponse('Bitte überprüfe das Enddatum deines Filters.');
            }

            if (!in_array(strtolower(trim($sortByString)), Todo::AVAILABLE_FILTERS)) {
                $responseUtils->errorResponse(
                    'Invalid key to sort by. Expected  ' .
                    '[' . implode(', ', Todo::AVAILABLE_FILTERS) . ']. Got ' . $sortByString
                );
            }

            $sortOrder = SortOrder::parse($sortOrderString);
        } catch (InvalidFilterTypeException|InvalidSortOrderException $e) {
            return $responseUtils->errorResponse($e->getMessage());
        }

        $todos = $user->getTodos(
            $showDeleted,
            $showDone,
            $startTime,
            $endTime,
            $sortByString,
            $sortOrder
        )->map(function (Todo $todo) {
            return $todo->asJsonObject();
        })->toArray();

        return new JsonResponse(
            data: $todos,
            status: 200
        );
    }

    public function createTodo(
        Request $request,
        JwtAuthenticator $authenticator,
        UserRepository $userRepository,
        TodoRepository $todoRepository,
        ResponseUtils $responseUtils
    ): JsonResponse
    {
        $userId = (int) $authenticator->authenticate($request)->getUser()->getUserIdentifier();
        $user = $userRepository->findUserById($userId);

        $todoData = json_decode(
            json: $request->getContent(),
            associative: true
        );

        $todo = new Todo();

        if (array_key_exists('title', $todoData)) {
            $title = $todoData['title'];

            if ($title == null || strlen($title) < 1) {
                return $responseUtils->errorResponse('Bitte gib einen Titel an');
            }

            $todo->setTitle($title);
        } else {
            return $responseUtils->errorResponse('Bitte gib einen Titel an');
        }

        if (array_key_exists('description', $todoData)) {
            $description = $todoData['description'];
            $todo->setDescription(($description != null && strlen($description) >= 1) ? $description : null);
        }

        if (array_key_exists('time', $todoData)) {
            $time = $todoData['time'];
            if ($time != null && strlen($time) >= 1) {
                $timeAsDateTime = DateTime::createFromFormat('Y-m-d\\TH:i', $time);

                if ($timeAsDateTime) {
                    $todo->setTime($timeAsDateTime);
                } else {
                    return $responseUtils->errorResponse('Bitte überprüfe die Zeit deiner Aufgabe.');
                }
            }
        }

        $todo->setDeleted(false);
        $todo->setDone(false);
        $todo->setCreatedAt(new DateTime());

        $user->addTodo($todo);

        $todoRepository->save($todo);
        $userRepository->save($user, true);

        return new JsonResponse(
            data: [
                'message' => 'Deine Aufgabe wurde erstellt',
                'todo' => $todo->asJsonObject()
            ],
            status: 200
        );
    }

    public function updateTodoTexts(
        int $id,
        Request $request,
        JwtAuthenticator $authenticator,
        TodoRepository $todoRepository,
        ResponseUtils $responseUtils
    ): JsonResponse
    {
        $userId = (int) $authenticator->authenticate($request)->getUser()->getUserIdentifier();
        $todo = $todoRepository->findById(todoId: $id, userId: $userId);

        if ($todo == null) {
            return $responseUtils->errorResponse(
                message: 'Diese Aufgabe existiert nicht',
                statusCode: 404
            );
        }

        $todoData = json_decode(
            json: $request->getContent(),
            associative: true
        );

        if (array_key_exists('title', $todoData)) {
            $title = $todoData['title'];

            if ($title == null || strlen($title) < 1) {
                return $responseUtils->errorResponse('Bitte gib einen Titel an');
            }

            $todo->setTitle($title);
        } else {
            return $responseUtils->errorResponse('Bitte gib einen Titel an');
        }

        if (array_key_exists('description', $todoData)) {
            $description = $todoData['description'];
            $todo->setDescription(($description != null && strlen($description) >= 1) ? $description : null);
        }

        $todo->setUpdatedAt(new DateTime());
        $todoRepository->save($todo, true);

        return new JsonResponse(
            data: [
                'message' => 'Aufgabe wurde aktualisiert',
                'todo' => $todo->asJsonObject()
            ],
            status: 200
        );
    }

    public function updateTodoTime(
        int $id,
        Request $request,
        JwtAuthenticator $authenticator,
        TodoRepository $todoRepository,
        ResponseUtils $responseUtils
    ): JsonResponse
    {
        $userId = (int) $authenticator->authenticate($request)->getUser()->getUserIdentifier();
        $todo = $todoRepository->findById(todoId: $id, userId: $userId);

        if ($todo == null) {
            return $responseUtils->errorResponse(
                message: 'Diese Aufgabe existiert nicht',
                statusCode: 404
            );
        }

        $todoData = json_decode(
            json: $request->getContent(),
            associative: true
        );

        if (array_key_exists('time', $todoData)) {
            $time = $todoData['time'];
            if ($time != null && strlen($time) >= 1) {
                $timeAsDateTime = DateTime::createFromFormat('Y-m-d\\TH:i', $time);

                if ($timeAsDateTime) {
                    $todo->setTime($timeAsDateTime);
                } else {
                    return $responseUtils->errorResponse('Bitte überprüfe die Zeit deiner Aufgabe.');
                }
            } else {
                $todo->setTime(null);
            }
        } else {
            return $responseUtils->errorResponse('Bitte gib eine Zeit für deine Aufgabe an.');
        }

        $todo->setUpdatedAt(new DateTime());
        $todoRepository->save($todo, true);

        return new JsonResponse(
            data: [
                'message' => 'Aufgabe wurde aktualisiert',
                'todo' => $todo->asJsonObject()
            ],
            status: 200
        );
    }

    public function setTodoDone(
        int $id,
        Request $request,
        JwtAuthenticator $authenticator,
        TodoRepository $todoRepository,
        ResponseUtils $responseUtils
    ): JsonResponse
    {
        $userId = (int) $authenticator->authenticate($request)->getUser()->getUserIdentifier();
        $todo = $todoRepository->findById(todoId: $id, userId: $userId);

        if ($todo == null) {
            return $responseUtils->errorResponse(
                message: 'Diese Aufgabe existiert nicht',
                statusCode: 404
            );
        }

        $value = $request->query->getBoolean('value');

        $todo->setDone($value);
        $todoRepository->save($todo, true);

        return new JsonResponse(
            data: [
                'message' => $value ? 'Deine Aufgabe wurde als erledigt markiert' : 'Deine Aufgabe ist nun wieder offen',
                'todo' => $todo->asJsonObject()
            ],
            status: 200
        );
    }

    public function deleteTodo(
        int $id,
        Request $request,
        JwtAuthenticator $authenticator,
        TodoRepository $todoRepository,
        ResponseUtils $responseUtils
    ): JsonResponse
    {
        $userId = (int) $authenticator->authenticate($request)->getUser()->getUserIdentifier();
        $todo = $todoRepository->findById(todoId: $id, userId: $userId);

        if ($todo == null) {
            return $responseUtils->errorResponse(
                message: 'Diese Aufgabe existiert nicht',
                statusCode: 404
            );
        }

        $todo->setDeleted(true);
        $todo->setDeletedAt(new DateTime());

        $todoRepository->save($todo, true);

        return new JsonResponse(
            data: [
                'message' => 'Deine Aufgabe wurde gelöscht',
                'todo' => $todo->asJsonObject()
            ],
            status: 200
        );
    }

    public function addNotification(
        int $todoId,
        Request $request,
        JwtAuthenticator $authenticator,
        TodoRepository $todoRepository,
        NotificationRepository $notificationRepository,
        ResponseUtils $responseUtils
    ): JsonResponse
    {
        $userId = (int) $authenticator->authenticate($request)->getUser()->getUserIdentifier();
        $todo = $todoRepository->findById(todoId: $todoId, userId: $userId);

        if ($todo == null) {
            return $responseUtils->errorResponse(
                message: 'Diese Aufgabe existiert nicht',
                statusCode: 404
            );
        }

        $notificationData = json_decode(
            json: $request->getContent(),
            associative: true
        );

        $notification = new Notification();

        if (array_key_exists('send_at', $notificationData)) {
            $time = $notificationData['send_at'];
            if ($time != null && strlen($time) >= 1) {
                $timeAsDateTime = DateTime::createFromFormat('Y-m-d\\TH:i', $time);

                if ($timeAsDateTime) {
                    $notification->setSendAt($timeAsDateTime);
                } else {
                    return $responseUtils->errorResponse('Bitte überprüfe deine Zeitangabe.');
                }
            } else {
                return $responseUtils->errorResponse('Bitte gib einen Benachrichtigungszeitpunkt an.');
            }
        } else {
            return $responseUtils->errorResponse('Bitte gib einen Benachrichtigungszeitpunkt an.');
        }

        $notification->setSent(false);
        $notification->setDeleted(false);
        $notification->setCreatedAt(new DateTime());
        $todo->addNotification($notification);

        $notificationRepository->save($notification);
        $todoRepository->save($todo, true);

        return new JsonResponse(
            data: [
                'message' => 'Benachrichtigung erstellt',
                'notification' => $notification->asJsonObject()
            ],
            status: 200
        );
    }

    public function removeNotification(
        int $todoId,
        int $notificationId,
        Request $request,
        JwtAuthenticator $authenticator,
        TodoRepository $todoRepository,
        NotificationRepository $notificationRepository,
        ResponseUtils $responseUtils
    ): JsonResponse
    {
        $userId = (int) $authenticator->authenticate($request)->getUser()->getUserIdentifier();

        $todo = $todoRepository->findById(
            todoId: $todoId,
            userId: $userId
        );

        $notification = $notificationRepository->findById(
            notificationId: $notificationId,
            todoId: $todoId
        );

        if ($todo == null) {
            return $responseUtils->errorResponse(
                message: 'Diese Aufgabe existiert nicht',
                statusCode: 404
            );
        }

        if ($notification == null) {
            return $responseUtils->errorResponse(
                message: 'Diese Benachrichtigung existiert nicht',
                statusCode: 404
            );
        }

        $notification->setDeleted(true);
        $notification->setDeletedAt(new DateTime());
        $notificationRepository->save($notification, true);

        return new JsonResponse(
            data: [
                'message' => 'Benachrichtung wurde gelöscht.',
                'notification' => $notification->asJsonObject()
            ],
            status: 200
        );
    }

    // API endpoint for testing the push notification functionality: Sends a push notification to firebase with a title & description
    public function sendPush(
        Request $request,
        ResponseUtils $responseUtils,
        FirebaseMessagingService $firebaseMessagingService
    ): JsonResponse
    {
        $message = CloudMessage::withTarget(
            type: 'token',
            value: 'diWQfQbURayTrR3BnTuZGl:APA91bHoln-xAgcjzXTAuNvSA5pZF8PVgOs9Uo-i4zfcR03GiAB08ENuQpGb6ooO7r5VTlvYBlQCEW9_BYW1w6Ncb-LFhpKnCJJ9o0QX__T7k8kF9vlgAID1fevVxe-zEPpBoU8vqWua'
        )
            ->withData([
                'title' => $request->query->get('title'),
                'description' => $request->query->get('description')
            ]);

        try {
            $firebaseMessagingService->getMessaging()->send($message);

            return new JsonResponse(
                data: [
                    'message' => 'Push message has been sent'
                ],
                status: 200
            );
        } catch (\Exception $e) {
            return $responseUtils->errorResponse($e->getMessage());
        }
    }

}
