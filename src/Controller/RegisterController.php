<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegisterType;
use App\Repository\UserRepository;
use App\Service\MailerServiceInterface;
use App\Service\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class RegisterController extends AbstractController
{
    public function __construct(
        private VerifyEmailHelperInterface $verifyEmailHelper,
        private UserManager $userManager,
        private MailerServiceInterface $mailerService
    ) {
    }

    /** Formulaire d'inscription publique. */
    #[Route('/inscription', name: 'app_register')]
    public function index(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(RegisterType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->userManager->emailExists($user->getEmail())) {
                $this->addFlash('alert', 'L\'email que vous avez renseigné existe déjà !! Connectez-vous.');

                return $this->redirectToRoute('app_login');
            }

            $this->userManager->createUser($user);

            $signatureComponents = $this->verifyEmailHelper->generateSignature(
                'app_verify_email',
                $user->getId(),
                $user->getEmail(),
                ['id' => $user->getId()]
            );
            $this->mailerService->sendEmailConfirmation($user, $signatureComponents);

            $this->addFlash('success', 'Votre demande d\'inscription est enregistrée.');

            return $this->redirectToRoute('app_send_email_confirm');
        }

        return $this->render('register/index.html.twig', [
            'form' => $form->createView(),
            'flash' => $this,
            'user' => $user,
        ]);
    }

    /** Page de confirmation d'envoi du mail de vérification. */
    #[Route('/confirmation-email-envoye', name: 'app_send_email_confirm')]
    public function sendConfirmEmail(): Response
    {
        return $this->render('register/register_confirm.html.twig');
    }

    /** Valide le lien signé reçu par mail et active le compte. */
    #[Route('/verification-email-inscription', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, UserRepository $userRepository): Response
    {
        $id = $request->query->getInt('id');

        if (0 === $id) {
            return $this->redirectToRoute('app_home');
        }

        $user = $userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException();
        }

        try {
            $this->verifyEmailHelper->validateEmailConfirmation($request->getUri(), $user->getId(), $user->getEmail());
        } catch (VerifyEmailExceptionInterface $e) {
            $this->addFlash('error', $e->getReason());

            return $this->redirectToRoute('app_register');
        }

        $user->setIsVerified(true);
        $user->setRoles(['ROLE_USER']);
        $userRepository->save($user, true);

        $this->addFlash('success', 'Votre compte est vérifié! Vous pouvez vous connecter.');

        return $this->redirectToRoute('app_login');
    }
}
