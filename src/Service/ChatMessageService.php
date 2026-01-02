<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\ChatMessage;
use App\Entity\User;
use App\Repository\ChatMessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final class ChatMessageService
{
    private const MAX_LENGTH = 2000;

    public function __construct(
        private EntityManagerInterface $em,
        private ChatMessageRepository $repo,
        private FriendshipService $friendship,
        private RealtimePublisher $publisher,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return list<ChatMessage>
     */
    public function getConversation(User $a, User $b, int $limit = 50): array
    {
        return $this->repo->findLastBetween($a, $b, $limit);
    }

    public function send(User $sender, User $recipient, string $rawContent): ChatMessage
    {
        $content = trim($rawContent);
        if ($content === '') {
            throw new \InvalidArgumentException('Message vide.');
        }
        if (mb_strlen($content) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException('Message trop long. (max '.self::MAX_LENGTH.' caractères)');
        }

        if (!$this->friendship->isFriend($sender, $recipient)) {
            throw new \RuntimeException('Accès refusé.');
        }

        $message = new ChatMessage($sender, $recipient, $content);
        $this->em->persist($message);
        $this->em->flush();

        // Publish to Mercure (both participants subscribe the same topic)
        $topic = $this->publisher->topicFor($sender, $recipient);
        $payload = [
            'type' => 'chat.message',
            'payload' => [
                'id' => $message->getId(),
                'senderId' => $sender->getId(),
                'recipientId' => $recipient->getId(),
                'content' => $message->getContent(),
                'createdAt' => $message->getCreatedAt()->format(DATE_ATOM),
            ],
        ];

        try {
            $this->publisher->publish($topic, $payload);
        } catch (\Throwable $e) {
            // Do not fail the request if realtime fails; log it.
            $this->logger->error('Mercure publish failed: '.$e->getMessage());
        }

        return $message;
    }
}
