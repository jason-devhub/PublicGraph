<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TestFrontendController extends AbstractController
{
    #[Route('/test-frontend', name: 'app_test_frontend', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('test_frontend/index.html.twig');
    }
}
