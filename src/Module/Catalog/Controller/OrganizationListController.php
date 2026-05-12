<?php

declare(strict_types=1);

namespace App\Module\Catalog\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OrganizationListController extends AbstractController
{
    #[Route('/organizations', name: 'app_organization_index', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('catalog/organization/list.html.twig', [
            'page_title' => 'Organisations',
        ]);
    }
}
