<?php

namespace App\Repository;

use App\Entity\Notification;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function save(Notification $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Notification $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    function findById(int $notificationId, int $todoId): ?Notification {
        try {
            return $this->createQueryBuilder('notification')
                ->select('notification')
                ->where('notification.id = :notificationId')
                ->andWhere('notification.todo = :todoId')
                ->andWhere('notification.deleted = 0')
                ->setMaxResults(1)
                ->setParameter('notificationId', $notificationId)
                ->setParameter('todoId', $todoId)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException) {
            return null;
        }
    }

    public function getNotificationsToSend(): array {
        $now = new DateTime();

        return $this->createQueryBuilder('notification')
            ->select('notification')
            ->where('notification.deleted = 0')
            ->andWhere('notification.sent = 0')
            ->andWhere('notification.send_at <= :currentTimestamp')
            ->setParameter('currentTimestamp', new DateTime())
            ->getQuery()
            ->getResult();
    }
}
