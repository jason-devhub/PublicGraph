<?php

declare(strict_types=1);

namespace App\Module\Legal\Controller;

use App\Module\Legal\Entity\Report;
use App\Module\Legal\Service\CloudflareTurnstileVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

final class PublicReportController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
        private readonly CloudflareTurnstileVerifier $turnstileVerifier,
    ) {
    }

    #[Route('/report', name: 'app_signaler', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('report', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            $token = $request->request->getString('cf-turnstile-response');
            if (!$this->turnstileVerifier->verify('' !== $token ? $token : null, $request)) {
                $this->addFlash('danger', 'Vérification anti-robot échouée.');

                return $this->redirectToRoute('app_signaler');
            }

            $entityType = $request->request->getString('entityType');
            if (!\in_array($entityType, Report::allowedEntityTypes(), true)) {
                $this->addFlash('danger', 'Type d’entité invalide.');

                return $this->redirectToRoute('app_signaler');
            }

            $identifier = trim($request->request->getString('entityIdentifier'));
            $reason = $request->request->getString('reason');
            if (!\in_array($reason, Report::allowedReasons(), true)) {
                $reason = Report::REASON_OTHER;
            }
            $description = trim($request->request->getString('description'));
            $contact = trim($request->request->getString('contactEmail'));

            if (\strlen($description) < 20) {
                $this->addFlash('danger', 'La description doit contenir au moins 20 caractères.');

                return $this->redirectToRoute('app_signaler');
            }

            $report = new Report();
            $report->setEntityType($entityType);
            $report->setEntityId(0);
            $report->setReason($reason);
            $report->setDescription($description."\nRéférence saisie : ".$identifier);
            $report->setContactEmail('' !== $contact ? $contact : null);

            $this->entityManager->persist($report);
            $this->entityManager->flush();

            $editor = $_ENV['EDITOR_EMAIL'] ?? 'contact@publicgraph.local';
            $email = (new Email())
                ->from('noreply@publicgraph.local')
                ->to($editor)
                ->subject('[PublicGraph] Nouveau signalement')
                ->text(sprintf(
                    "Type: %s\nRéférence: %s\nMotif: %s\n\n%s\n",
                    $entityType,
                    $identifier,
                    $reason,
                    $description,
                ));

            $this->mailer->send($email);

            return $this->render('legal/report_thanks.html.twig');
        }

        return $this->render('legal/report_form.html.twig', [
            'reasons' => [
                Report::REASON_FACTUALLY_INCORRECT => 'Erreur factuelle',
                Report::REASON_DEFAMATORY => 'Caractère diffamatoire',
                Report::REASON_PRIVACY => 'Atteinte à la vie privée',
                Report::REASON_COPYRIGHT => 'Propriété intellectuelle',
                Report::REASON_OTHER => 'Autre',
            ],
        ]);
    }
}
