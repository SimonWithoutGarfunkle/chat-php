<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Exception\FriendshipException;
use Doctrine\ORM\EntityManagerInterface;

final class FriendshipService
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    /**
     * Adds $other as a friend of $me. Mutual and idempotent.
     *
     * @throws FriendshipException when trying to friend self
     */
    public function addFriend(User $me, User $other): void
    {
        if ($me === $other || ($me->getId() !== null && $me->getId() === $other->getId())) {
            throw new FriendshipException('Vous ne pouvez pas vous ajouter vous-même en ami.');
        }

        // Idempotent: if already friends, do nothing
        if ($me->getFriends()->contains($other)) {
            return;
        }

        $me->addFriend($other); // ensures mutual link
        $this->em->flush();
    }

    /**
     * Removes $other from $me's friends. Mutual and idempotent.
     *
     * @throws FriendshipException when trying to unfriend self
     */
    public function removeFriend(User $me, User $other): void
    {
        if ($me === $other || ($me->getId() !== null && $me->getId() === $other->getId())) {
            throw new FriendshipException('Action invalide.');
        }

        // Idempotent: if not friends, no-op
        if (!$me->getFriends()->contains($other)) {
            return;
        }

        $me->removeFriend($other); // ensures mutual unlink
        $this->em->flush();
    }
}
