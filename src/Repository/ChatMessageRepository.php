<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\ChatMessage;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChatMessage>
 */
final class ChatMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatMessage::class);
    }

    /**
     * @return list<ChatMessage>
     */
    public function findLastBetween(User $a, User $b, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('(m.sender = :a AND m.recipient = :b) OR (m.sender = :b AND m.recipient = :a)')
            ->setParameter('a', $a)
            ->setParameter('b', $b)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit);

        $result = $qb->getQuery()->getResult();
        // Return in chronological order (oldest first)
        return array_reverse($result);
    }
}
