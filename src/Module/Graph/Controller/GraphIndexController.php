<?php

declare(strict_types=1);

namespace App\Module\Graph\Controller;

use App\Module\Graph\Model\GraphQueryParams;
use App\Module\Person\Entity\Person;
use App\Module\Person\Repository\PersonRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GraphIndexController extends AbstractController
{
    #[Route('/graph', name: 'app_graph_index', methods: ['GET'])]
    public function __invoke(Request $request, PersonRepository $personRepository): Response
    {
        $params = GraphQueryParams::fromRequest($request);
        $focusPerson = null;
        $focus = $request->query->get('focus');
        if (\is_string($focus) && '' !== trim($focus)) {
            $p = $personRepository->findBySlug(trim($focus));
            if ($p instanceof Person && Person::STATUS_APPROVED === $p->getStatus()) {
                $focusPerson = $p;
            }
        }

        return $this->render('graph/index.html.twig', [
            'graph_params' => $params,
            'focus_person' => $focusPerson,
        ]);
    }
}
