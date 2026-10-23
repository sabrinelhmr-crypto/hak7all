<?php

namespace App\Controller;

use App\Entity\PushSubscription;
use App\Entity\User;
use App\Repository\PushSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/push')]
class PushController extends AbstractController
{
    #[Route('/cle-publique', name: 'app_push_public_key', methods: ['GET'])]
    public function publicKey(): JsonResponse
    {
        $key = (string) $this->getParameter('app.vapid_public_key');

        return $this->json(['publicKey' => $key]);
    }

    #[Route('/abonnement', name: 'app_push_subscribe', methods: ['POST'])]
    public function subscribe(
        Request $request,
        PushSubscriptionRepository $subscriptionRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Données invalides.'], Response::HTTP_BAD_REQUEST);
        }

        $endpoint = (string) ($data['endpoint'] ?? '');
        $keys = $data['keys'] ?? [];

        if ('' === $endpoint || !is_array($keys) || empty($keys['p256dh']) || empty($keys['auth'])) {
            return $this->json(['error' => 'Abonnement invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $existing = $subscriptionRepository->findOneByEndpoint($endpoint);
        if (null !== $existing) {
            $existing->setUser($user)->setKeys([
                'p256dh' => (string) $keys['p256dh'],
                'auth' => (string) $keys['auth'],
            ]);
            $entityManager->flush();

            return $this->json(['ok' => true]);
        }

        $subscription = (new PushSubscription())
            ->setUser($user)
            ->setEndpoint($endpoint)
            ->setKeys([
                'p256dh' => (string) $keys['p256dh'],
                'auth' => (string) $keys['auth'],
            ]);

        $entityManager->persist($subscription);
        $entityManager->flush();

        return $this->json(['ok' => true]);
    }

    #[Route('/desabonnement', name: 'app_push_unsubscribe', methods: ['POST'])]
    public function unsubscribe(
        Request $request,
        PushSubscriptionRepository $subscriptionRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Données invalides.'], Response::HTTP_BAD_REQUEST);
        }

        $endpoint = (string) ($data['endpoint'] ?? '');
        if ('' === $endpoint) {
            return $this->json(['error' => 'Données invalides.'], Response::HTTP_BAD_REQUEST);
        }

        $existing = $subscriptionRepository->findOneByEndpoint($endpoint);
        if (null !== $existing && $existing->getUser() === $user) {
            $entityManager->remove($existing);
            $entityManager->flush();
        }

        return $this->json(['ok' => true]);
    }
}
