<?php

declare(strict_types=1);

namespace App\Module\User\Controller;

use App\Module\User\Entity\User;
use App\Module\User\Form\RegistrationFormType;
use App\Module\User\Repository\UserRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $userRepository,
        private readonly MailerInterface $mailer,
    ) {
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $user = new User();
        $user->setRoles(['ROLE_USER']);
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plain = (string) $form->get('plainPassword')->getData();
            $user->setPassword($this->passwordHasher->hashPassword($user, $plain));
            $token = bin2hex(random_bytes(32));
            $user->setEmailVerificationToken($token);
            $user->setEmailVerificationTokenExpiresAt(new \DateTimeImmutable('+24 hours'));

            $this->userRepository->save($user, true);

            $verifyUrl = $this->generateUrl('app_verify_email', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
            $email = (new TemplatedEmail())
                ->from('noreply@publicgraph.local')
                ->to($user->getEmail())
                ->subject('Validez votre compte')
                ->htmlTemplate('email/verify_email.html.twig')
                ->context(['verifyUrl' => $verifyUrl]);

            $this->mailer->send($email);

            $this->addFlash('success', 'Un e-mail de validation vous a été envoyé.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('user/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/register/verify/{token}', name: 'app_verify_email', requirements: ['token' => '[a-fA-F0-9]{64}'], methods: ['GET'])]
    public function verifyEmail(string $token): Response
    {
        $user = $this->userRepository->findOneBy(['emailVerificationToken' => $token]);
        if (!$user instanceof User) {
            throw $this->createNotFoundException();
        }

        $expires = $user->getEmailVerificationTokenExpiresAt();
        if (null !== $expires && $expires < new \DateTimeImmutable()) {
            $this->addFlash('danger', 'Ce lien de validation a expiré.');

            return $this->redirectToRoute('app_login');
        }

        $user->setEmailVerifiedAt(new \DateTimeImmutable());
        $user->setEmailVerificationToken(null);
        $user->setEmailVerificationTokenExpiresAt(null);
        $this->userRepository->save($user, true);

        $this->addFlash('success', 'Votre adresse e-mail est validée.');

        return $this->redirectToRoute('app_contributor_dashboard');
    }

    #[Route('/register/resend-verification', name: 'app_resend_verification_email', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function resendVerificationEmail(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('resend_verification', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (null !== $user->getEmailVerifiedAt()) {
            $this->addFlash('info', 'Votre adresse est déjà validée.');

            return $this->redirectToRoute('app_contributor_dashboard');
        }

        $token = bin2hex(random_bytes(32));
        $user->setEmailVerificationToken($token);
        $user->setEmailVerificationTokenExpiresAt(new \DateTimeImmutable('+24 hours'));
        $this->userRepository->save($user, true);

        $verifyUrl = $this->generateUrl('app_verify_email', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
        $email = (new TemplatedEmail())
            ->from('noreply@publicgraph.local')
            ->to($user->getEmail())
            ->subject('Validez votre compte')
            ->htmlTemplate('email/verify_email.html.twig')
            ->context(['verifyUrl' => $verifyUrl]);

        $this->mailer->send($email);
        $this->addFlash('success', 'Un nouvel e-mail de validation vous a été envoyé.');

        return $this->redirectToRoute('app_contributor_dashboard');
    }
}
