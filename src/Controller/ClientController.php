<?php

namespace App\Controller;

use App\Entity\ServiceRequest;
use App\Entity\User;
use App\Message\EscalateUrgentRequest;
use App\Message\NotifyNewRequest;
use App\Repository\ServiceRequestRepository;
use App\Repository\TradeRepository;
use App\Repository\WorkerProfileRepository;
use App\Util\Phone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/client')]
class ClientController extends AbstractController
{
    public function __construct(private readonly MessageBusInterface $bus)
    {
    }

    #[Route('', name: 'app_client_search', methods: ['GET'])]
    public function search(
        Request $request,
        TradeRepository $tradeRepository,
        WorkerProfileRepository $workerProfileRepository,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_CLIENT');
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (!$user->isVerified()) {
            $this->addFlash('warning', 'Vérifiez votre e-mail pour pouvoir envoyer des demandes.');
        }

        $tradeId = $request->query->get('metier');
        $onlyAvailable = $request->query->has('disponibles');

        $trade = null;
        if (null !== $tradeId && '' !== $tradeId) {
            $trade = $tradeRepository->find($tradeId);
        }

        $profiles = $workerProfileRepository->search($trade, null, (bool) $onlyAvailable, $user->getCity());

        return $this->render('client/search.html.twig', [
            'trades' => $tradeRepository->findAllOrdered(),
            'profiles' => $profiles,
            'selectedTrade' => $trade,
            'onlyAvailable' => (bool) $onlyAvailable,
        ]);
    }

    #[Route('/demande/{id}', name: 'app_client_request_create', methods: ['POST'])]
    public function createRequest(
        int $id,
        Request $request,
        WorkerProfileRepository $workerProfileRepository,
        ServiceRequestRepository $serviceRequestRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_CLIENT');
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (!$user->isVerified()) {
            $this->addFlash('error', "Votre e-mail doit être vérifié pour envoyer une demande.");

            return $this->redirectToRoute('app_client_search');
        }

        $profile = $workerProfileRepository->find($id);
        if (null === $profile) {
            throw $this->createNotFoundException();
        }

        $worker = $profile->getUser();
        if (null === $worker || !$worker->isWorker()) {
            throw $this->createAccessDeniedException();
        }

        if (!$worker->isVerified()) {
            $this->addFlash('error', "Cet artisan n'est pas disponible pour le moment.");

            return $this->redirectToRoute('app_client_search');
        }

        $this->validateCsrfToken('request_'.$id, $request->request->get('_token'));

        $message = trim((string) $request->request->get('message', ''));
        if ('' === $message) {
            $this->addFlash('error', 'Décrivez votre problème avant d’envoyer la demande.');

            return $this->redirectToRoute('app_client_search');
        }

        $isUrgent = $request->request->has('urgent');
        if ($isUrgent) {
            if (!$profile->isAvailable()) {
                $this->addFlash('error', "Cet artisan n'est pas disponible maintenant ; impossible d'envoyer une demande urgente.");

                return $this->redirectToRoute('app_client_search');
            }

            $since = (new \DateTimeImmutable())->modify('-1 hour');
            $count = $serviceRequestRepository->countUrgentSince($user, $since);
            if ($count >= 3) {
                $this->addFlash('error', "Vous avez atteint la limite de 3 demandes urgentes par heure.");

                return $this->redirectToRoute('app_client_search');
            }
        }

        $serviceRequest = (new ServiceRequest())
            ->setClient($user)
            ->setWorker($worker)
            ->setMessage($message)
            ->setIsUrgent($isUrgent);

        $entityManager->persist($serviceRequest);
        $entityManager->flush();

        $this->bus->dispatch(new NotifyNewRequest($serviceRequest->getId()));

        if ($isUrgent) {
            $this->bus->dispatch(new EscalateUrgentRequest($serviceRequest->getId()), [new DelayStamp(180000)]);
        }

        $this->addFlash('success', $isUrgent ? 'Demande urgente envoyée.' : 'Demande envoyée.');

        return $this->redirectToRoute('app_client_requests');
    }

    #[Route('/demandes', name: 'app_client_requests', methods: ['GET'])]
    public function requests(ServiceRequestRepository $serviceRequestRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_CLIENT');
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $requests = $serviceRequestRepository->findForClient($user);

        return $this->render('client/requests.html.twig', [
            'requests' => $requests,
        ]);
    }

    #[Route('/api/dernieres-demandes', name: 'app_client_requests_json', methods: ['GET'])]
    public function requestsJson(ServiceRequestRepository $serviceRequestRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_CLIENT');
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $requests = $serviceRequestRepository->findForClient($user);
        $data = [];

        foreach ($requests as $request) {
            $worker = $request->getWorker();
            $profile = $worker?->getWorkerProfile();
            $data[] = [
                'id' => $request->getId(),
                'status' => $request->getStatus(),
                'statusLabel' => $request->getStatusLabel(),
                'isUrgent' => $request->isUrgent(),
                'isAccepted' => $request->isAccepted(),
                'workerName' => $worker?->getFullName(),
                'workerPhone' => $request->isAccepted() && null !== $worker ? Phone::format($worker->getPhone()) : null,
                'trade' => $profile?->getTrade()?->getName(),
                'createdAt' => $request->getCreatedAt()->format('d/m/Y H:i'),
            ];
        }

        return $this->json(['requests' => $data]);
    }

    private function validateCsrfToken(string $id, ?string $token): void
    {
        if (null === $token || !$this->isCsrfTokenValid('request_'.$id, $token)) {
            throw $this->createAccessDeniedException();
        }
    }
}
