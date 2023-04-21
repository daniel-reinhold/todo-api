<?php

namespace App\Service;

use App\Entity\Notification;
use InvalidArgumentException;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\CloudMessage;

class FirebaseMessagingService
{
    private Messaging $messaging;

    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    public function getMessaging(): Messaging
    {
        return $this->messaging;
    }


    /**
     * @param Notification $notification The notification to send
     * @return bool true when the notification has been sent successfully, false otherwise
     */
    public function sendPushMessage(Notification $notification): bool {
        $message = CloudMessage::new()
            ->withData([
                'title' => $notification->getTodo()->getTitle()
            ]);

        try {
            $this->messaging->send($message);

            return true;
        } catch (FirebaseException|InvalidArgumentException $e) {
            return false;
        }
    }

}