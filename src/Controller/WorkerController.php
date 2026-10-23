<?php

namespace App\Controller;

use App\Entity\ServiceRequest;
use App\Entity\User;
use App\Form\WorkerProfileData;
use App\Form\WorkerProfileFormType;
use App\Repository\ServiceRequestRepository;
use App\Repository\WorkerProfileRepository;
use App\Service\RequestNotifier;
use App\Util\Phone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/artisan')]
class WorkerController extends AbstractController
{
    #[Route('', name: 'app_worker_profile', methods: ['GET', 'POST'])]
    public function profile(
        Request $request,
        ServiceRequestRepository $serviceRequestRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_WORKER');
        $user = $this->getUser();
        if (!$user instanceof User || !$user->isWorker()) {
            throw $this->createAccessDeniedException();
        }

        $profile = $user->getWorkerProfile();
        if (null === $profile) {
            throw $this->createNotFoundException();
        }

        if (!$user->isVerified()) {
            $this->addFlash('warning', 'Vérifiez votre e-mail pour apparaître dans les résultats de recherche.');

            return $this->render('worker/profile.html.twig', [
                'user' => $user,
                'profile' => $profile,
                'form' => null,
                'pendingCount' => 0,
            ]);
        }

        $data = new WorkerProfileData();
        $data->fullName = $user->getFullName();
        $data->phone = Phone::format($user->getPhone());
        $data->city = $user->getCity();
        $data->trade = $profile->getTrade();
        $data->rateTnd = $profile->getRateTnd();
        $data->isAvailable = $profile->isAvailable();
        $data->phonePublic = $profile->isPhonePublic();

        $form = $this->createForm(WorkerProfileFormType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setFullName($data->fullName);
            $user->setPhone(Phone::normalize($data->phone));
            $user->setCity($data->city);
            $profile->setTrade($data->trade);
            $profile->setRateTnd($data->rateTnd);
            $profile->setIsAvailable($data->isAvailable);
            $profile->setPhonePublic($data->phonePublic);
            $profile->touch();

            $entityManager->flush();

            $this->addFlash('success', 'Profil mis à jour.');

            return $this->redirectToRoute('app_worker_profile');
        }

        $pendingCount = $serviceRequestRepository->countPendingForWorker($user);

        return $this->render('worker/profile.html.twig', [
            'user' => $user,
            'profile' => $profile,
            'form' => $form,
            'pendingCount' => $pendingCount,
        ]);
    }

    #[Route('/demandes', name: 'app_worker_requests', methods: ['GET'])]
    public function requests(
        ServiceRequestRepository $serviceRequestRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_WORKER');
        $user = $this->getUser();
        if (!$user instanceof User || !$user->isWorker()) {
            throw $this->createAccessDeniedException();
        }

        $requests = $serviceRequestRepository->findForWorker($user);
        $pendingCount = $serviceRequestRepository->countPendingForWorker($user);

        return $this->render('worker/requests.html.twig', [
            'requests' => $requests,
            'pendingCount' => $pendingCount,
        ]);
    }

    #[Route('/demande/{id}/accepter', name: 'app_worker_request_accept', methods: ['POST'])]
    public function accept(
        ServiceRequest $serviceRequest,
        Request $request,
        EntityManagerInterface $entityManager,
        RequestNotifier $notifier,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_WORKER');
        $user = $this->getUser();
        if (!$user instanceof User || $serviceRequest->getWorker() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if (!$serviceRequest->isPending()) {
            $this->addFlash('error', 'Cette demande a déjà été traitée.');

            return $this->redirectToRoute('app_worker_requests');
        }

        $this->validateCsrfToken('request_'.$serviceRequest->getId(), $request->request->get('_token'));

        $serviceRequest->accept();
        $serviceRequest->markSeen();
        $entityManager->flush();

        $notifier->notifyStatus($serviceRequest);

        $this->addFlash('success', 'Demande acceptée. Le numéro du client est maintenant visible.');

        return $this->redirectToRoute('app_worker_requests');
    }

    #[Route('/demande/{id}/refuser', name: 'app_worker_request_decline', methods: ['POST'])]
    public function decline(
        ServiceRequest $serviceRequest,
        Request $request,
        EntityManagerInterface $entityManager,
        RequestNotifier $notifier,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_WORKER');
        $user = $this->getUser();
        if (!$user instanceof User || $serviceRequest->getWorker() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if (!$serviceRequest->isPending()) {
            $this->addFlash('error', 'Cette demande a déjà été traitée.');

            return $this->redirectToRoute('app_worker_requests');
        }

        $this->validateCsrfToken('request_'.$serviceRequest->getId(), $request->request->get('_token'));

        $serviceRequest->decline();
        $serviceRequest->markSeen();
        $entityManager->flush();

        $notifier->notifyStatus($serviceRequest);

        $this->addFlash('success', 'Demande refusée.');

        return $this->redirectToRoute('app_worker_requests');
    }

    #[Route('/demande/{id}/vu', name: 'app_worker_request_seen', methods: ['POST'])]
    public function markSeen(
        ServiceRequest $serviceRequest,
        Request $request,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_WORKER');
        $user = $this->getUser();
        if (!$user instanceof User || $serviceRequest->getWorker() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $this->validateCsrfToken('request_'.$serviceRequest->getId(), $request->request->get('_token'));

        if (null === $serviceRequest->getSeenAt()) {
            $serviceRequest->markSeen();
            $entityManager->flush();
        }

        return $this->json(['ok' => true]);
    }

    #[Route('/api/compteur', name: 'app_worker_badge', methods: ['GET'])]
    public function badge(ServiceRequestRepository $serviceRequestRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_WORKER');
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $count = $serviceRequestRepository->countPendingForWorker($user);

        return $this->json(['pending' => $count]);
    }

    private function validateCsrfToken(string $id, ?string $token): void
    {
        if (null === $token || !$this->isCsrfTokenValid('request_'.$id, $token)) {
            throw $this->createAccessDeniedException();
        }
    }
}
