<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\WorkerProfile;
use App\Form\RegistrationData;
use App\Form\RegistrationFormType;
use App\Repository\CityRepository;
use App\Repository\TradeRepository;
use App\Repository\UserRepository;
use App\Util\Phone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class AuthController extends AbstractController
{
    public function __construct(
        private readonly VerifyEmailHelperInterface $verifyEmailHelper,
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/', name: 'app_home', methods: ['GET', 'POST'])]
    public function home(
        Request $request,
        AuthenticationUtils $authenticationUtils,
        UserRepository $userRepository,
        CityRepository $cityRepository,
        TradeRepository $tradeRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): Response {
        if ($this->getUser()) {
            $user = $this->getUser();
            if ($user instanceof User) {
                return $this->redirectToRoute($user->isWorker() ? 'app_worker_profile' : 'app_client_search');
            }
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        $data = new RegistrationData();
        $data->role = $request->query->get('role', User::ROLE_CLIENT);
        if (!in_array($data->role, [User::ROLE_CLIENT, User::ROLE_WORKER], true)) {
            $data->role = User::ROLE_CLIENT;
        }

        $form = $this->createForm(RegistrationFormType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = mb_strtolower(trim($data->email));
            if (null !== $userRepository->findOneByEmail($email)) {
                $this->addFlash('error', 'Cet e-mail a déjà un compte. Connectez-vous.');

                return $this->redirectToRoute('app_home');
            }

            $normalizedPhone = Phone::normalize($data->phone);
            if (null !== $userRepository->findOneByPhone($normalizedPhone)) {
                $this->addFlash('error', 'Ce numéro de téléphone est déjà utilisé.');

                return $this->redirectToRoute('app_home');
            }

            $user = (new User())
                ->setEmail($email)
                ->setFullName($data->fullName)
                ->setPhone($normalizedPhone)
                ->setCity($data->city)
                ->setRole($data->role)
                ->setPassword($passwordHasher->hashPassword(new User(), $data->plainPassword));

            $entityManager->persist($user);

            if (User::ROLE_WORKER === $user->getRole()) {
                $profile = (new WorkerProfile())
                    ->setUser($user)
                    ->setTrade($data->trade)
                    ->setRateTnd($data->rateTnd)
                    ->setIsAvailable(false)
                    ->setPhonePublic($data->phonePublic);

                $entityManager->persist($profile);
                $user->setWorkerProfile($profile);
            }

            $entityManager->flush();

            $this->sendVerificationEmail($user);

            $this->addFlash('success', 'Compte créé. Vérifiez votre e-mail pour activer votre compte.');

            return $this->redirectToRoute('app_home');
        }

        $cities = $cityRepository->findAllOrdered();
        $trades = $tradeRepository->findAllOrdered();

        return $this->render('auth/home.html.twig', [
            'loginError' => $error,
            'lastUsername' => $lastUsername,
            'registrationForm' => $form,
            'cities' => $cities,
            'trades' => $trades,
        ]);
    }

    #[Route('/connexion', name: 'app_login', methods: ['POST'])]
    public function login(): never
    {
        throw new \LogicException('Géré par le firewall de sécurité.');
    }

    #[Route('/deconnexion', name: 'app_logout', methods: ['GET', 'POST'])]
    public function logout(): never
    {
        throw new \LogicException('Géré par le firewall de sécurité.');
    }

    #[Route('/verification-email', name: 'app_verify_email')]
    public function verifyEmail(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $id = $request->query->get('id');
        if (null === $id) {
            $this->addFlash('error', 'Lien de vérification invalide.');

            return $this->redirectToRoute('app_home');
        }

        $user = $userRepository->find($id);
        if (null === $user) {
            $this->addFlash('error', 'Lien de vérification invalide.');

            return $this->redirectToRoute('app_home');
        }

        try {
            $this->verifyEmailHelper->validateEmailConfirmationFromRequest($request, (string) $user->getId(), $user->getEmail());
        } catch (\Throwable) {
            $this->addFlash('error', 'Lien de vérification expiré ou invalide.');

            return $this->redirectToRoute('app_home');
        }

        if (!$user->isVerified()) {
            $user->markEmailVerified();
            $entityManager->flush();
        }

        $this->addFlash('success', 'E-mail vérifié avec succès. Vous pouvez vous connecter.');

        return $this->redirectToRoute('app_home');
    }

    #[Route('/renvoyer-verification', name: 'app_resend_verification', methods: ['POST'])]
    public function resendVerification(Request $request, UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_home');
        }

        if ($user->isVerified()) {
            $this->addFlash('success', 'Votre e-mail est déjà vérifié.');

            return $this->redirectToRoute($user->isWorker() ? 'app_worker_profile' : 'app_client_search');
        }

        $this->sendVerificationEmail($user);
        $this->addFlash('success', 'Un nouvel e-mail de vérification a été envoyé.');

        return $this->redirectToRoute('app_home');
    }

    private function sendVerificationEmail(User $user): void
    {
        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            'app_verify_email',
            (string) $user->getId(),
            $user->getEmail(),
            ['id' => $user->getId()]
        );

        $email = (new Email())
            ->from('no-reply@hak7all.tn')
            ->to($user->getEmail())
            ->subject('Hak 7all — Vérification de votre adresse e-mail')
            ->html(sprintf(
                '<p>Bonjour %s,</p><p>Confirmez votre adresse e-mail en cliquant sur le lien suivant :</p><p><a href="%s">Vérifier mon e-mail</a></p><p>Si vous n’êtes pas à l’origine de cette inscription, ignorez ce message.</p>',
                htmlspecialchars($user->getFullName()),
                $signatureComponents->getSignedUrl()
            ));

        $this->mailer->send($email);
    }

    #[Route('/conditions', name: 'app_terms', methods: ['GET'])]
    public function terms(): Response
    {
        return $this->render('pages/terms.html.twig');
    }

    #[Route('/confidentialite', name: 'app_privacy', methods: ['GET'])]
    public function privacy(): Response
    {
        return $this->render('pages/privacy.html.twig');
    }
}
