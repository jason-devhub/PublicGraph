<?php

declare(strict_types=1);

namespace App\Module\Catalog\Controller;

use App\Module\Legislation\Entity\RevolvingDoor;
use App\Module\Legislation\Repository\RevolvingDoorRepository;
use App\Module\Person\Entity\Person;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class RevolvingDoorShowController extends AbstractController
{
    #[Route('/revolving-doors/{id}', name: 'app_revolving_door_show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function __invoke(int $id, RevolvingDoorRepository $revolvingDoorRepository): Response
    {
        $door = $revolvingDoorRepository->findOneApprovedById($id);
        if (!$door instanceof RevolvingDoor) {
            throw new NotFoundHttpException('Porte tournante introuvable.');
        }

        $person = $door->getPerson();
        if (!$person instanceof Person || Person::STATUS_APPROVED !== $person->getStatus()) {
            throw new NotFoundHttpException('Porte tournante introuvable.');
        }

        $response = $this->render('catalog/revolving_door/show.html.twig', [
            'door' => $door,
            'person' => $person,
        ]);

        $response->setPublic();
        $response->setMaxAge(86400);

        return $response;
    }
}
