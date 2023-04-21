<?php

namespace App\Command;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use App\Service\FirebaseMessagingService;
use DateTime;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('app:push:send', 'Sends all push notifications which have to be sent')]
class SendPushMessagesCommand extends Command
{
    private NotificationRepository $notificationRepository;
    private FirebaseMessagingService $firebaseMessagingService;

    public function __construct(NotificationRepository $notificationRepository, FirebaseMessagingService $firebaseMessagingService)
    {
        parent::__construct();

        $this->notificationRepository = $notificationRepository;
        $this->firebaseMessagingService = $firebaseMessagingService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $now = (new DateTime())->getTimestamp();

        $io = new SymfonyStyle($input, $output);
        $io->success('Sending push notifications');

        $notifications = $this->notificationRepository->getNotificationsToSend();

        $io->info('Now: ' . (new DateTime())->getTimestamp());
        $sentNotificationCount = 0;
        foreach ($notifications as $notification) {
            if ($notification instanceof Notification) {
                $io->info('ID: ' . $notification->getId() . ", timestamp: " . $notification->getSendAt()->getTimestamp());
                $messageSentSuccessfully = $this->firebaseMessagingService->sendPushMessage($notification);

                if ($messageSentSuccessfully) {
                    $notification->setSent(true);
                    $this->notificationRepository->save($notification, true);
                    $sentNotificationCount++;
                }
            }
        }

        $io->success('Sent ' . $sentNotificationCount . " push notifications");

        return Command::SUCCESS;
    }

}