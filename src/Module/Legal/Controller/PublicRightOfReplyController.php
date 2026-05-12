<?php

declare(strict_types=1);

namespace App\Module\Legal\Controller;

use App\Module\Legal\Entity\RightOfReplyRequest;
use App\Module\Legal\Service\CloudflareTurnstileVerifier;
use App\Module\Person\Entity\Person;
use App\Module\Person\Repository\PersonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

final class PublicRightOfReplyController extends AbstractController
{
    public function __construct(
        private readonly PersonRepository $personRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
        private readonly CloudflareTurnstileVerifier $turnstileVerifier,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    #[Route('/right-of-reply/{slug}', name: 'app_public_right_of_reply', methods: ['GET', 'POST'])]
    public function __invoke(string $slug, Request $request): Response
    {
        $person = $this->personRepository->findBySlug($slug);
        if (!$person instanceof Person || Person::STATUS_APPROVED !== $person->getStatus()) {
            throw $this->createNotFoundException();
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('ror', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException();
            }

            $token = $request->request->getString('cf-turnstile-response');
            if (!$this->turnstileVerifier->verify('' !== $token ? $token : null, $request)) {
                $this->addFlash('danger', 'Vérification anti-robot échouée.');

                return $this->redirectToRoute('app_public_right_of_reply', ['slug' => $slug]);
            }

            $body = trim($request->request->getString('requestText'));
            if (\strlen($body) < 50) {
                $this->addFlash('danger', 'Le texte doit contenir au moins 50 caractères.');

                return $this->redirectToRoute('app_public_right_of_reply', ['slug' => $slug]);
            }

            $upload = $request->files->get('justificationFile');
            if (!$upload instanceof UploadedFile || \UPLOAD_ERR_OK !== $upload->getError()) {
                $this->addFlash('danger', 'Le PDF justificatif est obligatoire.');

                return $this->redirectToRoute('app_public_right_of_reply', ['slug' => $slug]);
            }

            $mime = $upload->getMimeType();
            if ('application/pdf' !== $mime || ($upload->getSize() ?? 0) > 5 * 1024 * 1024) {
                $this->addFlash('danger', 'PDF uniquement, 5 Mo maximum.');

                return $this->redirectToRoute('app_public_right_of_reply', ['slug' => $slug]);
            }

            $realPath = $upload->getRealPath();
            if (false === $realPath) {
                $this->addFlash('danger', 'Fichier PDF invalide.');

                return $this->redirectToRoute('app_public_right_of_reply', ['slug' => $slug]);
            }

            $head = @file_get_contents($realPath, false, null, 0, 5);
            if (!\is_string($head) || !str_starts_with($head, '%PDF-')) {
                $this->addFlash('danger', 'Le fichier doit être un PDF valide (signature binaire).');

                return $this->redirectToRoute('app_public_right_of_reply', ['slug' => $slug]);
            }

            $dir = $this->projectDir.'/var/uploads/right-of-reply';
            if (!is_dir($dir)) {
                mkdir($dir, 0750, true);
            }

            $filename = Uuid::v7()->toRfc4122().'.pdf';
            $upload->move($dir, $filename);
            $pdfPath = 'var/uploads/right-of-reply/'.$filename;

            $ror = new RightOfReplyRequest();
            $ror->setPerson($person);
            $ror->setRequesterName(trim($request->request->getString('requesterName')));
            $ror->setRequesterQuality(trim($request->request->getString('requesterQuality')));
            $ror->setRequesterEmail(trim($request->request->getString('requesterEmail')));
            $ror->setRequesterPhone($this->nullable($request->request->getString('requesterPhone')));
            $ror->setIdentityPdfPath($pdfPath);
            $ror->setRequestType($request->request->getString('requestType') ?: RightOfReplyRequest::TYPE_OTHER);
            $ror->setBody($body);

            $this->entityManager->persist($ror);
            $this->entityManager->flush();

            $editor = $_ENV['EDITOR_EMAIL'] ?? 'contact@publicgraph.local';
            $email = (new Email())
                ->from('noreply@publicgraph.local')
                ->to($editor)
                ->subject('[URGENT] Droit de réponse — '.$person->getSlug())
                ->text($body);

            $this->mailer->send($email);

            return $this->render('legal/right_of_reply_thanks.html.twig');
        }

        return $this->render('legal/right_of_reply_form.html.twig', [
            'person' => $person,
            'types' => [
                RightOfReplyRequest::TYPE_RECTIFICATION => 'Rectification',
                RightOfReplyRequest::TYPE_REMOVAL => 'Retrait',
                RightOfReplyRequest::TYPE_ADDITION => 'Ajout',
                RightOfReplyRequest::TYPE_OTHER => 'Autre',
            ],
        ]);
    }

    private function nullable(string $s): ?string
    {
        return '' === trim($s) ? null : $s;
    }
}
