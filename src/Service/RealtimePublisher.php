<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

final class RealtimePublisher
{
    public function __construct(private HubInterface $hub)
    {
    }

    public function topicFor(User $a, User $b): string
    {
        $aId = (int) $a->getId();
        $bId = (int) $b->getId();
        $min = min($aId, $bId);
        $max = max($aId, $bId);
        return sprintf('chat/conversation/%d-%d', $min, $max);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function publish(string $topic, array $payload): void
    {
        $update = new Update(
            topics: $topic,
            data: json_encode($payload, JSON_THROW_ON_ERROR)
        );
        $this->hub->publish($update);
    }
}
