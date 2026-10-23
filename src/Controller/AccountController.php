<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AccountData;
use App\Form\AccountFormType;
use App\Repository\UserRepository;
use App\Util\Phone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/compte')]
class AccountController extends AbstractController
{
    #[Route('', name: 'app_account', methods: ['GET', 'POST'])]
    public function index(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $data = new AccountData();
        $data->fullName = $user->getFullName();
        $data->phone = Phone::format($user->getPhone());
        $data->city = $user->getCity();

        $form = $this->createForm(AccountFormType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $normalizedPhone = Phone::normalize($data->phone);
            $existing = $userRepository->findOneByPhone($normalizedPhone);
            if (null !== $existing && $existing->getId() !== $user->getId()) {
                $this->addFlash('error', 'Ce numéro de téléphone est déjà utilisé.');

                return $this->redirectToRoute('app_account');
            }

            $user->setFullName($data->fullName);
            $user->setPhone($normalizedPhone);
            $user->setCity($data->city);
            $entityManager->flush();

            $this->addFlash('success', 'Compte mis à jour.');

            return $this->redirectToRoute('app_account');
        }

        return $this->render('account/index.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    #[Route('/supprimer', name: 'app_account_delete', methods: ['POST'])]
    public function delete(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $this->validateCsrfToken('delete_account', $request->request->get('_token'));

        $user->setDeletedAt(new \DateTimeImmutable());
        $user->setEmail('deleted-'.$user->getId().'-'.bin2hex(random_bytes(4)).'@hak7all.invalid');
        $user->setPhone('00000000'.$user->getId());
        $user->setFullName('[Compte supprimé]');
        $user->setPassword(bin2hex(random_bytes(32)));

        $entityManager->flush();

        $request->getSession()->invalidate();
        $this->container->get('security.token_storage')->setToken(null);

        $this->addFlash('success', 'Votre compte a été supprimé.');

        return $this->redirectToRoute('app_home');
    }

    private function validateCsrfToken(string $id, ?string $token): void
    {
        if (null === $token || !$this->isCsrfTokenValid($id, $token)) {
            throw $this->createAccessDeniedException();
        }
    }
}
