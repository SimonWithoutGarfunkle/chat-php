<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ChatMessageService;
use App\Service\FriendshipService;
use App\Service\RealtimePublisher;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ChatController extends AbstractController
{
    public function __construct(private RealtimePublisher $publisher)
    {
    }

    #[Route('/chat/{friendId}', name: 'app_chat_conversation', requirements: ['friendId' => '\\d+'], methods: ['GET'])]
    public function conversation(int $friendId, UserRepository $users, FriendshipService $friendship, ChatMessageService $chat): Response
    {
        /** @var User $me */
        $me = $this->getUser();
        $friend = $users->find($friendId);
        if (!$friend instanceof User) {
            return new Response('Utilisateur introuvable', Response::HTTP_NOT_FOUND);
        }
        if (!$friendship->isFriend($me, $friend)) {
            return new Response('Accès refusé', Response::HTTP_FORBIDDEN);
        }

        $messages = $chat->getConversation($me, $friend, 50);
        $topic = $this->publisher->topicFor($me, $friend);

        return $this->render('chat/conversation.html.twig', [
            'friend' => $friend,
            'me' => $me,
            'messages' => $messages,
            'topic' => $topic,
        ]);
    }

    #[Route('/chat/{friendId}/send', name: 'app_chat_send', requirements: ['friendId' => '\\d+'], methods: ['POST'])]
    public function send(int $friendId, Request $request, UserRepository $users, ChatMessageService $chat): JsonResponse
    {
        /** @var User $me */
        $me = $this->getUser();
        $friend = $users->find($friendId);
        if (!$friend instanceof User) {
            return new JsonResponse(['error' => 'not_found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode((string) $request->getContent(), true);
        $content = is_array($data) && isset($data['content']) ? (string) $data['content'] : '';

        try {
            $message = $chat->send($me, $friend, $content);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => 'invalid', 'message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'forbidden'], Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse([
            'id' => $message->getId(),
            'senderId' => $message->getSender()->getId(),
            'recipientId' => $message->getRecipient()->getId(),
            'content' => $message->getContent(),
            'createdAt' => $message->getCreatedAt()->format(DATE_ATOM),
        ]);
    }
}
