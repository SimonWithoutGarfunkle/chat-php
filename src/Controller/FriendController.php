<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Exception\FriendshipException;
use App\Repository\UserRepository;
use App\Service\FriendshipService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[IsGranted('ROLE_USER')]
final class FriendController extends AbstractController
{
    #[Route('/friends', name: 'app_friends_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        /** @var User $me */
        $me = $this->getUser();
        $friends = $me->getFriends();

        // Very simple: list all users except self and those already friends
        $allUsers = $userRepository->findAll();
        $candidates = array_values(array_filter($allUsers, function (User $u) use ($me, $friends) {
            if ($u->getId() === $me->getId()) {
                return false;
            }
            return !$friends->contains($u);
        }));

        return $this->render('friends/index.html.twig', [
            'friends' => $friends,
            'candidates' => $candidates,
        ]);
    }

    #[Route('/friends/add/{id}', name: 'app_friends_add', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function add(int $id, Request $request, UserRepository $userRepository, FriendshipService $service, CsrfTokenManagerInterface $csrf): Response
    {
        /** @var User $me */
        $me = $this->getUser();

        $token = new CsrfToken('add_friend_'.$id, (string) $request->request->get('_token'));
        if (!$csrf->isTokenValid($token)) {
            $this->addFlash('error', 'CSRF token invalide.');
            return $this->redirectToRoute('app_friends_index');
        }

        $other = $userRepository->find($id);
        if (!$other) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('app_friends_index');
        }

        try {
            $service->addFriend($me, $other);
            $this->addFlash('success', 'Ami ajouté.');
        } catch (FriendshipException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_friends_index');
    }

    #[Route('/friends/remove/{id}', name: 'app_friends_remove', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function remove(int $id, Request $request, UserRepository $userRepository, FriendshipService $service, CsrfTokenManagerInterface $csrf): Response
    {
        /** @var User $me */
        $me = $this->getUser();

        $token = new CsrfToken('remove_friend_'.$id, (string) $request->request->get('_token'));
        if (!$csrf->isTokenValid($token)) {
            $this->addFlash('error', 'CSRF token invalide.');
            return $this->redirectToRoute('app_friends_index');
        }

        $other = $userRepository->find($id);
        if (!$other) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('app_friends_index');
        }

        try {
            $service->removeFriend($me, $other);
            $this->addFlash('success', 'Ami supprimé.');
        } catch (FriendshipException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_friends_index');
    }
}
