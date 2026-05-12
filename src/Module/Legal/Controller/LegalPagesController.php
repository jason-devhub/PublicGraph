<?php

declare(strict_types=1);

namespace App\Module\Legal\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LegalPagesController extends AbstractController
{
    private const PLACEHOLDER_ADDRESS = '[Adresse du siège — à compléter avant production]';

    #[Route('/legal-notice', name: 'app_mentions_legales', methods: ['GET'])]
    public function mentionsLegales(): Response
    {
        return $this->render('legal/mentions_legales.html.twig', [
            'adresse_a_completer' => self::PLACEHOLDER_ADDRESS,
        ]);
    }

    #[Route('/terms', name: 'app_cgu', methods: ['GET'])]
    public function cgu(): Response
    {
        return $this->render('legal/cgu.html.twig');
    }

    #[Route('/privacy', name: 'app_confidentialite', methods: ['GET'])]
    public function confidentialite(): Response
    {
        return $this->render('legal/confidentialite.html.twig');
    }

    #[Route('/editorial-charter', name: 'app_charte_editoriale', methods: ['GET'])]
    public function charteEditoriale(): Response
    {
        return $this->render('legal/charte_editoriale.html.twig');
    }

    #[Route('/about', name: 'app_a_propos', methods: ['GET'])]
    public function aPropos(): Response
    {
        return $this->render('legal/a_propos.html.twig');
    }

    #[Route('/contact', name: 'app_contact', methods: ['GET'])]
    public function contact(): Response
    {
        return $this->render('legal/contact.html.twig');
    }

    #[Route('/right-of-reply', name: 'app_droit_de_reponse', methods: ['GET'])]
    public function droitDeReponse(): Response
    {
        return $this->render('legal/droit_de_reponse.html.twig');
    }
}
